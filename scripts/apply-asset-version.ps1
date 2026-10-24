param(
    [string]$Version = $env:GITHUB_SHA
)

$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
if ([string]::IsNullOrWhiteSpace($Version)) {
    $Version = (git -C $repoRoot rev-parse --short=12 HEAD).Trim()
}
$safeVersion = ($Version -replace '[^A-Za-z0-9._-]', '').Substring(0, [Math]::Min(12, ($Version -replace '[^A-Za-z0-9._-]', '').Length))
if ([string]::IsNullOrWhiteSpace($safeVersion)) { throw 'Asset version is empty.' }

$pattern = '(?<attr>href|src)="(?<asset>(?:fonts/fonts|style|atelier-iconic|order|main)\.(?:css|js))(?:\?v=[^"]*)?"'
Get-ChildItem -LiteralPath $repoRoot -Filter '*.html' -File | ForEach-Object {
    $content = Get-Content -LiteralPath $_.FullName -Raw -Encoding utf8
    $updated = [regex]::Replace($content, $pattern, '${attr}="${asset}?v=' + $safeVersion + '"')
    if ($updated -ne $content) {
        Set-Content -LiteralPath $_.FullName -Value $updated -Encoding utf8 -NoNewline
    }
}

Write-Output "Applied asset cache version: $safeVersion"
