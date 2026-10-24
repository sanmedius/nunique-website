$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$errors = [System.Collections.Generic.List[string]]::new()
$pages = Get-ChildItem -LiteralPath $repoRoot -Filter '*.html' -File

foreach ($page in $pages) {
    $content = Get-Content -LiteralPath $page.FullName -Raw -Encoding utf8
    $relative = $page.Name
    $h1Count = ([regex]::Matches($content, '<h1(?:\s|>)', 'IgnoreCase')).Count
    if ($h1Count -ne 1) { $errors.Add("${relative}: expected exactly one h1, found $h1Count") }
    if ($content -match '<style(?:\s|>)') { $errors.Add("${relative}: inline style block found") }
    if ($content -match '\sonclick=') { $errors.Add("${relative}: inline onclick handler found") }
    if ($content -match 'fonts\.googleapis\.com|fonts\.gstatic\.com') { $errors.Add("${relative}: remote Google Font reference found") }
    if ($content -match 'geschmack-2|geschmack_2|lang-btn') { $errors.Add("${relative}: obsolete form or language code found") }

    foreach ($match in [regex]::Matches($content, '<a\b[^>]*target="_blank"[^>]*>', 'IgnoreCase')) {
        if ($match.Value -notmatch 'rel="[^"]*noopener') { $errors.Add("${relative}: target=_blank link lacks rel=noopener") }
    }

    $ids = [regex]::Matches($content, '\sid="([^"]+)"', 'IgnoreCase') | ForEach-Object { $_.Groups[1].Value }
    $ids | Group-Object | Where-Object Count -gt 1 | ForEach-Object { $errors.Add("${relative}: duplicate id '$($_.Name)'") }
}

$requiredFiles = @(
    'style.css', 'atelier-iconic.css', 'order.css', 'main.js', 'order.js',
    'fonts/fonts.css', 'fonts/urbanist-latin.woff2', 'fonts/urbanist-italic-latin.woff2',
    'fonts/cormorant-garamond-latin.woff2', 'fonts/cormorant-garamond-italic-latin.woff2'
)
foreach ($required in $requiredFiles) {
    if (-not (Test-Path -LiteralPath (Join-Path $repoRoot $required) -PathType Leaf)) { $errors.Add("Missing required asset: $required") }
}

if ($errors.Count) {
    $errors | ForEach-Object { Write-Error $_ }
    exit 1
}
Write-Output "Validated $($pages.Count) HTML pages."
