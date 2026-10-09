$ErrorActionPreference = 'Stop'

$workspaceRoot = Split-Path -Parent $PSScriptRoot
$utf8NoBom = [Text.UTF8Encoding]::new($false)
$dimensionCache = @{}

function Read-BigEndian32 {
    param([byte[]]$Bytes, [int]$Offset)
    return ([int]$Bytes[$Offset] -shl 24) -bor
           ([int]$Bytes[$Offset + 1] -shl 16) -bor
           ([int]$Bytes[$Offset + 2] -shl 8) -bor
           [int]$Bytes[$Offset + 3]
}

function Read-LittleEndian24 {
    param([byte[]]$Bytes, [int]$Offset)
    return [int]$Bytes[$Offset] -bor
           ([int]$Bytes[$Offset + 1] -shl 8) -bor
           ([int]$Bytes[$Offset + 2] -shl 16)
}

function Get-RasterDimensions {
    param([Parameter(Mandatory)][string]$Path)

    $bytes = [IO.File]::ReadAllBytes($Path)
    $extension = [IO.Path]::GetExtension($Path).ToLowerInvariant()

    if ($extension -eq '.png') {
        return @{ Width = Read-BigEndian32 $bytes 16; Height = Read-BigEndian32 $bytes 20 }
    }

    if ($extension -in @('.jpg', '.jpeg')) {
        $position = 2
        $startOfFrameMarkers = @(0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF)
        while ($position -lt $bytes.Length - 9) {
            while ($position -lt $bytes.Length -and $bytes[$position] -ne 0xFF) { $position++ }
            while ($position -lt $bytes.Length -and $bytes[$position] -eq 0xFF) { $position++ }
            if ($position -ge $bytes.Length) { break }
            $marker = [int]$bytes[$position]
            $position++
            if ($marker -in @(0x01, 0xD8, 0xD9)) { continue }
            if ($position + 1 -ge $bytes.Length) { break }
            $segmentLength = ([int]$bytes[$position] -shl 8) -bor [int]$bytes[$position + 1]
            if ($marker -in $startOfFrameMarkers) {
                $height = ([int]$bytes[$position + 3] -shl 8) -bor [int]$bytes[$position + 4]
                $width = ([int]$bytes[$position + 5] -shl 8) -bor [int]$bytes[$position + 6]
                return @{ Width = $width; Height = $height }
            }
            if ($segmentLength -lt 2) { break }
            $position += $segmentLength
        }
    }

    if ($extension -eq '.webp') {
        $chunk = [Text.Encoding]::ASCII.GetString($bytes, 12, 4)
        if ($chunk -eq 'VP8X') {
            return @{
                Width = (Read-LittleEndian24 $bytes 24) + 1
                Height = (Read-LittleEndian24 $bytes 27) + 1
            }
        }
        if ($chunk -eq 'VP8 ') {
            $width = ([int]$bytes[26] -bor ([int]$bytes[27] -shl 8)) -band 0x3FFF
            $height = ([int]$bytes[28] -bor ([int]$bytes[29] -shl 8)) -band 0x3FFF
            return @{ Width = $width; Height = $height }
        }
        if ($chunk -eq 'VP8L') {
            $b1 = [int]$bytes[21]
            $b2 = [int]$bytes[22]
            $b3 = [int]$bytes[23]
            $b4 = [int]$bytes[24]
            return @{
                Width = 1 + $b1 + (($b2 -band 0x3F) -shl 8)
                Height = 1 + (($b2 -band 0xC0) -shr 6) + ($b3 -shl 2) + (($b4 -band 0x0F) -shl 10)
            }
        }
    }

    throw "Unsupported or malformed image: $Path"
}

function Get-ImageDimensions {
    param([Parameter(Mandatory)][string]$RelativePath)

    if ($dimensionCache.ContainsKey($RelativePath)) { return $dimensionCache[$RelativePath] }
    $fullPath = Join-Path $workspaceRoot ($RelativePath -replace '/', [IO.Path]::DirectorySeparatorChar)
    if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
        throw "Image does not exist: $RelativePath"
    }

    if ([IO.Path]::GetExtension($fullPath).ToLowerInvariant() -eq '.svg') {
        $svg = [IO.File]::ReadAllText($fullPath)
        $viewBox = [regex]::Match($svg, '\bviewBox=["'']\s*[-\d.]+\s+[-\d.]+\s+([\d.]+)\s+([\d.]+)\s*["'']', 'IgnoreCase')
        if (-not $viewBox.Success) { throw "SVG has no usable viewBox: $RelativePath" }
        $dimensions = @{
            Width = [int][Math]::Round([double]::Parse($viewBox.Groups[1].Value, [Globalization.CultureInfo]::InvariantCulture))
            Height = [int][Math]::Round([double]::Parse($viewBox.Groups[2].Value, [Globalization.CultureInfo]::InvariantCulture))
        }
    } else {
        $dimensions = Get-RasterDimensions -Path $fullPath
    }

    if ($dimensions.Width -le 0 -or $dimensions.Height -le 0) {
        throw "Invalid image dimensions: $RelativePath"
    }
    $dimensionCache[$RelativePath] = $dimensions
    return $dimensions
}

function Set-ImageAttribute {
    param([string]$Tag, [string]$Name, [int]$Value)
    $pattern = '(?i)(?<prefix>\b' + [regex]::Escape($Name) + '\s*=\s*)(?<quote>["''])[^"'']*(?:\k<quote>)'
    if ([regex]::IsMatch($Tag, $pattern)) {
        return [regex]::Replace($Tag, $pattern, { param($match) $match.Groups['prefix'].Value + $match.Groups['quote'].Value + $Value + $match.Groups['quote'].Value }, 1)
    }
    return $Tag -replace '\s*/?>$', " $Name=`"$Value`"/>"
}

$changedFiles = [Collections.Generic.List[string]]::new()
Get-ChildItem -LiteralPath $workspaceRoot -Filter '*.html' -File | Sort-Object Name | ForEach-Object {
    $original = [IO.File]::ReadAllText($_.FullName)
    $updated = [regex]::Replace(
        $original,
        '<img\b[^>]*\bsrc=["''](?<src>images/[^"'']+)["''][^>]*>',
        {
            param($match)
            $dimensions = Get-ImageDimensions -RelativePath $match.Groups['src'].Value
            $tag = Set-ImageAttribute -Tag $match.Value -Name 'width' -Value $dimensions.Width
            return Set-ImageAttribute -Tag $tag -Name 'height' -Value $dimensions.Height
        },
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )
    if ($updated -ne $original) {
        [IO.File]::WriteAllText($_.FullName, $updated, $utf8NoBom)
        $changedFiles.Add($_.Name)
    }
}

if ($changedFiles.Count -gt 0) {
    Write-Host ('Synchronized image dimensions in: ' + ($changedFiles -join ', '))
} else {
    Write-Host 'Image dimensions are already synchronized.'
}
