$ErrorActionPreference = 'Stop'

$workspaceRoot = Split-Path -Parent $PSScriptRoot
$configPath = Join-Path $PSScriptRoot 'site-config.json'
$siteConfig = Get-Content -LiteralPath $configPath -Raw | ConvertFrom-Json
$productionDomain = ([string]$siteConfig.productionDomain).Trim().TrimEnd('/')
$socialImagePath = ([string]$siteConfig.socialImage).Trim()
$businessTelephone = ([string]$siteConfig.businessTelephone).Trim()
$telephoneHref = 'tel:+' + ($businessTelephone -replace '[^0-9]', '')

$domainUri = $null
if (-not [Uri]::TryCreate($productionDomain, [UriKind]::Absolute, [ref]$domainUri) -or
    $domainUri.Scheme -ne 'https' -or
    $domainUri.AbsolutePath -ne '/') {
    throw 'productionDomain must be an HTTPS origin without a path.'
}
if (-not $socialImagePath.StartsWith('/')) {
    throw 'socialImage must be a root-relative path beginning with /.'
}
$socialImageUrl = $productionDomain + $socialImagePath

$utf8NoBom = [System.Text.UTF8Encoding]::new($false)
$canonicalPattern = '<link\b(?=[^>]*\brel=["'']canonical["''])[^>]*>'
$openGraphPattern = '<meta\b(?=[^>]*\bproperty=["'']og:url["''])[^>]*>'
$openGraphImagePattern = '<meta\b(?=[^>]*\bproperty=["'']og:image["''])[^>]*>'
$twitterImagePattern = '<meta\b(?=[^>]*\bname=["'']twitter:image["''])[^>]*>'
$twitterCardPattern = '<meta\b(?=[^>]*\bname=["'']twitter:card["''])[^>]*>'
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

    $working = $original
    $working = [regex]::Replace(
        $working,
        '<meta\b(?=[^>]*\bname=["'']keywords["''])[^>]*>\s*',
        '',
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )
    $working = [regex]::Replace(
        $working,
        'href=["'']tel:\+4989215464622?["'']',
        'href="' + $telephoneHref + '"',
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )
    if (-not [regex]::IsMatch($working, $openGraphImagePattern, [Text.RegularExpressions.RegexOptions]::IgnoreCase)) {
        $working = [regex]::Replace(
            $working,
            $twitterCardPattern,
            { param($match) $match.Value + '<meta content="' + $socialImageUrl + '" property="og:image"/>' },
            [Text.RegularExpressions.RegexOptions]::IgnoreCase
        )
    }
    if (-not [regex]::IsMatch($working, $twitterImagePattern, [Text.RegularExpressions.RegexOptions]::IgnoreCase)) {
        $working = [regex]::Replace(
            $working,
            $openGraphImagePattern,
            { param($match) $match.Value + '<meta content="' + $socialImageUrl + '" name="twitter:image"/>' },
            [Text.RegularExpressions.RegexOptions]::IgnoreCase
        )
    }

    $updated = [regex]::Replace(
        $working,
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
    $updated = [regex]::Replace(
        $updated,
        $openGraphImagePattern,
        { param($match) Set-TagAttribute -Tag $match.Value -Attribute 'content' -Value $socialImageUrl },
        [Text.RegularExpressions.RegexOptions]::IgnoreCase
    )
    $updated = [regex]::Replace(
        $updated,
        $twitterImagePattern,
        { param($match) Set-TagAttribute -Tag $match.Value -Attribute 'content' -Value $socialImageUrl },
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

$indexPath = Join-Path $workspaceRoot 'index.html'
$indexOriginal = [IO.File]::ReadAllText($indexPath)
$schemaPattern = '(?s)(?<open><script\b[^>]*\bid=["'']localBusinessData["''][^>]*>)(?<json>.*?)(?<close></script>)'
$schemaMatch = [regex]::Match($indexOriginal, $schemaPattern, [Text.RegularExpressions.RegexOptions]::IgnoreCase)
if ($schemaMatch.Success) {
    $schema = $schemaMatch.Groups['json'].Value | ConvertFrom-Json
    $schema.'@id' = "$productionDomain/#business"
    $schema.url = "$productionDomain/"
    $schema.image = $socialImageUrl
    $schema.logo = "$productionDomain/images/Logo_quadrat_solo.svg"
    $schema.telephone = $businessTelephone
    $schemaJson = $schema | ConvertTo-Json -Depth 10
    $schemaReplacement = $schemaMatch.Groups['open'].Value + "`n" + $schemaJson + "`n" + $schemaMatch.Groups['close'].Value
    $indexUpdated = $indexOriginal.Substring(0, $schemaMatch.Index) + $schemaReplacement + $indexOriginal.Substring($schemaMatch.Index + $schemaMatch.Length)
    if ($indexUpdated -ne $indexOriginal) {
        [IO.File]::WriteAllText($indexPath, $indexUpdated, $utf8NoBom)
        if (-not $changedFiles.Contains('index.html')) { $changedFiles.Add('index.html') }
    }
}

$sitemapUrls = foreach ($pagePath in $siteConfig.indexablePages) {
    $cleanPath = ([string]$pagePath).Trim()
    if (-not $cleanPath.StartsWith('/')) { throw "Invalid sitemap path: $cleanPath" }
    $location = if ($cleanPath -eq '/') { "$productionDomain/" } else { $productionDomain + $cleanPath }
    "  <url><loc>$([Security.SecurityElement]::Escape($location))</loc></url>"
}
$sitemapContent = @(
    '<?xml version="1.0" encoding="UTF-8"?>'
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
    $sitemapUrls
    '</urlset>'
) -join "`n"
$sitemapContent += "`n"
$sitemapPath = Join-Path $workspaceRoot 'sitemap.xml'
$existingSitemap = if (Test-Path -LiteralPath $sitemapPath) { [IO.File]::ReadAllText($sitemapPath) } else { '' }
if ($existingSitemap -ne $sitemapContent) {
    [IO.File]::WriteAllText($sitemapPath, $sitemapContent, $utf8NoBom)
    $changedFiles.Add('sitemap.xml')
}

$robotsPath = Join-Path $workspaceRoot 'robots.txt'
$robotsOriginal = [IO.File]::ReadAllText($robotsPath)
$robotsWithoutSitemap = [regex]::Replace($robotsOriginal, '(?im)^Sitemap:\s*.*(?:\r?\n)?', '').TrimEnd()
$robotsUpdated = $robotsWithoutSitemap + "`n`nSitemap: $productionDomain/sitemap.xml`n"
if ($robotsUpdated -ne $robotsOriginal) {
    [IO.File]::WriteAllText($robotsPath, $robotsUpdated, $utf8NoBom)
    $changedFiles.Add('robots.txt')
}

if ($changedFiles.Count -gt 0) {
    Write-Host ('Applied production domain to: ' + ($changedFiles -join ', '))
} else {
    Write-Host 'Production domain is already synchronized.'
}
