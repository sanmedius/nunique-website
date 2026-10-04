param([switch]$Check)

$projectRoot = Split-Path -Parent $PSScriptRoot
$pages = Get-ChildItem -LiteralPath $projectRoot -File -Filter '*.html'

function Get-SharedBlock([string]$path, [string]$kind) {
    $content = [System.IO.File]::ReadAllText($path)
    $match = [regex]::Match($content, "(?s)<!-- SHARED $kind START -->.*?<!-- SHARED $kind END -->")
    if (-not $match.Success) { throw "The $kind block was not found in $path." }
    $match.Value.Trim()
}

$header = Get-SharedBlock (Join-Path $projectRoot 'shared-reference/header-reference.html') 'HEADER'
$footer = Get-SharedBlock (Join-Path $projectRoot 'shared-reference/footer-reference.html') 'FOOTER'
$changes = @()

foreach ($page in $pages) {
    $original = [System.IO.File]::ReadAllText($page.FullName)
    $updated = [regex]::Replace($original, '(?s)<!-- SHARED HEADER START -->.*?<!-- SHARED HEADER END -->', $header, 1)
    $updated = [regex]::Replace($updated, '(?s)<!-- SHARED FOOTER START -->.*?<!-- SHARED FOOTER END -->', $footer, 1)
    if ($updated -cne $original) {
        $changes += $page.Name
        if (-not $Check) { [System.IO.File]::WriteAllText($page.FullName, $updated, [System.Text.UTF8Encoding]::new($false)) }
    }
}

if ($changes.Count -eq 0) { Write-Output 'Shared header and footer blocks are synchronized.' }
elseif ($Check) { Write-Output ('Pages needing synchronization: ' + ($changes -join ', ')); exit 1 }
else { Write-Output ('Synchronized: ' + ($changes -join ', ')) }
