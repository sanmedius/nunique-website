$ErrorActionPreference = 'Stop'

$workspaceRoot = Split-Path -Parent $PSScriptRoot
$configPath = Join-Path $PSScriptRoot 'site-config.json'
$siteConfig = Get-Content -LiteralPath $configPath -Raw | ConvertFrom-Json
$productionDomain = ([string]$siteConfig.productionDomain).Trim().TrimEnd('/')

$domainUri = $null
if (-not [Uri]::TryCreate($productionDomain, [UriKind]::Absolute, [ref]$domainUri) -or
    $domainUri.Scheme -ne 'https' -or
    $domainUri.AbsolutePath -ne '/') {
    throw 'productionDomain must be an HTTPS origin without a path.'
}

$utf8NoBom = [System.Text.UTF8Encoding]::new($false)
$canonicalPattern = '<link\b(?=[^>]*\brel=["'']canonical["''])[^>]*>'
$openGraphPattern = '<meta\b(?=[^>]*\bproperty=["'']og:url["''])[^>]*>'
$changedFiles = [System.Collections.Generic.List[string]]::new()

function Set-TagAttribute {
    param(
        [Parameter(Mandatory)][string]$Tag,
        [Parameter(Mandatory)][string]$Attribute,
        [Parameter(Mandatory)][string]$Value
    )

    $attributePattern = '(?i)(?<prefix>\b' + [regex]::Escape($Attribute) + '\s*=\s*)(?<quote>["''])[^"'']*(?:\k<quote>)'
    if (-not [regex]::IsMatch($Tag, $attributePattern)) {
        throw "Missing $Attribute attribute in: $Tag"
    }

    return [regex]::Replace(
        $Tag,
        $attributePattern,
        { param($match) $match.Groups['prefix'].Value + $match.Groups['quote'].Value + $Value + $match.Groups['quote'].Value },
        1
    )
}

Get-ChildItem -LiteralPath $workspaceRoot -Filter '*.html' -File | Sort-Object Name | ForEach-Object {
    $htmlPath = $_.FullName
    $original = [IO.File]::ReadAllText($htmlPath)
    $pageUrl = if ($_.Name -eq 'index.html') { "$productionDomain/" } else { "$productionDomain/$($_.BaseName)" }

    $canonicalMatches = [regex]::Matches($original, $canonicalPattern, [Text.RegularExpressions.RegexOptions]::IgnoreCase)
    $openGraphMatches = [regex]::Matches($original, $openGraphPattern, [Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($canonicalMatches.Count -ne 1) {
        throw "$($_.Name): expected exactly one canonical link."
    }
    if ($openGraphMatches.Count -ne 1) {
        throw "$($_.Name): expected exactly one og:url tag."
    }

    $updated = [regex]::Replace(
        $original,
        $canonicalPattern,
        { param($match) Set-TagAttribute -Tag $match.Value -Attribute 'href' -Value $pageUrl },
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )
    $updated = [regex]::Replace(
        $updated,
        $openGraphPattern,
        { param($match) Set-TagAttribute -Tag $match.Value -Attribute 'content' -Value $pageUrl },
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )

    if ($updated -ne $original) {
        [IO.File]::WriteAllText($htmlPath, $updated, $utf8NoBom)
        $changedFiles.Add($_.Name)
    }
}

$backendConfigPath = Join-Path $workspaceRoot 'backend/config.php'
$backendOriginal = [IO.File]::ReadAllText($backendConfigPath)
$siteUrlPattern = '(?<prefix>["'']site_url["'']\s*=>\s*["''])[^"'']*(?<suffix>["''])'
$siteUrlMatches = [regex]::Matches($backendOriginal, $siteUrlPattern, [Text.RegularExpressions.RegexOptions]::IgnoreCase)
if ($siteUrlMatches.Count -ne 1) {
    throw 'backend/config.php: expected exactly one site_url setting.'
}
$backendUpdated = [regex]::Replace(
    $backendOriginal,
    $siteUrlPattern,
    { param($match) $match.Groups['prefix'].Value + $productionDomain + $match.Groups['suffix'].Value },
    [Text.RegularExpressions.RegexOptions]::IgnoreCase
)
if ($backendUpdated -ne $backendOriginal) {
    [IO.File]::WriteAllText($backendConfigPath, $backendUpdated, $utf8NoBom)
    $changedFiles.Add('backend/config.php')
}

if ($changedFiles.Count -gt 0) {
    Write-Host ('Applied production domain to: ' + ($changedFiles -join ', '))
} else {
    Write-Host 'Production domain is already synchronized.'
}
