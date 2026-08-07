[CmdletBinding()]
param([string]$OutputDirectory = '')

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$extensionRoot = Join-Path $repoRoot 'opencart\noveraile'
$marketplaceRoot = Join-Path $repoRoot 'marketplace'
if (-not $OutputDirectory) { $OutputDirectory = Join-Path $repoRoot 'release' }
$OutputDirectory = [System.IO.Path]::GetFullPath($OutputDirectory)
$manifest = Get-Content -LiteralPath (Join-Path $extensionRoot 'install.json') -Raw | ConvertFrom-Json

if ($manifest.version -notmatch '^\d+\.\d+\.\d+$') { throw 'install.json version must use semantic x.y.z format.' }

$requiredFiles = @(
    'install.json',
    'admin\controller\module\noveraile.php',
    'admin\language\en-gb\module\noveraile.php',
    'admin\view\template\module\noveraile.twig',
    'catalog\controller\event\theme.php',
    'catalog\view\stylesheet\noveraile.css',
    'admin\controller\module\feed.php',
    'admin\model\module\feed.php',
    'catalog\model\shipping\tier.php'
)
foreach ($relativePath in $requiredFiles) {
    if (-not (Test-Path -LiteralPath (Join-Path $extensionRoot $relativePath) -PathType Leaf)) { throw "Required extension file is missing: $relativePath" }
}

$forbiddenFiles = Get-ChildItem -LiteralPath $extensionRoot -Recurse -Force -File | Where-Object {
    $_.Name -in @('.env', '.DS_Store', 'Thumbs.db') -or $_.Extension -in @('.log', '.map', '.psd')
}
if ($forbiddenFiles) { throw "Forbidden development files found: $($forbiddenFiles.FullName -join ', ')" }

$secretMatches = Get-ChildItem -LiteralPath $extensionRoot -Recurse -File |
    Where-Object { $_.Extension -in @('.php', '.twig', '.js', '.json', '.md') } |
    Select-String -Pattern 'sk_live_[A-Za-z0-9]+|whsec_[A-Za-z0-9]+' -List
if ($secretMatches) { throw "A possible production secret was found: $($secretMatches.Path -join ', ')" }

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null
$installerArchive = Join-Path $OutputDirectory 'noveraile.ocmod.zip'
$marketplaceArchive = Join-Path $OutputDirectory ("noveraile-theme-{0}-marketplace.zip" -f $manifest.version)
$stageDirectory = Join-Path $OutputDirectory '.noveraile-marketplace-stage'

foreach ($target in @($installerArchive, $marketplaceArchive)) {
    if (Test-Path -LiteralPath $target) { Remove-Item -LiteralPath $target -Force }
}
if (Test-Path -LiteralPath $stageDirectory) {
    $resolvedStage = [System.IO.Path]::GetFullPath($stageDirectory)
    if (-not $resolvedStage.StartsWith($OutputDirectory, [System.StringComparison]::OrdinalIgnoreCase)) { throw 'Refusing to clean a staging directory outside the release directory.' }
    Remove-Item -LiteralPath $resolvedStage -Recurse -Force
}

Compress-Archive -Path (Join-Path $extensionRoot '*') -DestinationPath $installerArchive -CompressionLevel Optimal

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($installerArchive)
try {
    $entries = @($zip.Entries | ForEach-Object { $_.FullName.Replace('/', '\') })
    foreach ($relativePath in $requiredFiles) {
        if ($entries -notcontains $relativePath) { throw "Installer archive validation failed; missing entry: $relativePath" }
    }
    if ($entries | Where-Object { $_ -like 'upload\*' }) { throw 'OpenCart 4 packages must not wrap extension files in an upload directory.' }
} finally {
    $zip.Dispose()
}

New-Item -ItemType Directory -Force -Path $stageDirectory | Out-Null
Copy-Item -LiteralPath $installerArchive -Destination (Join-Path $stageDirectory 'noveraile.ocmod.zip')
Copy-Item -LiteralPath (Join-Path $extensionRoot 'README.md') -Destination (Join-Path $stageDirectory 'DOCUMENTATION-EN.md')
Copy-Item -LiteralPath (Join-Path $extensionRoot 'INSTALL-RU.md') -Destination (Join-Path $stageDirectory 'DOCUMENTATION-RU.md')
Copy-Item -LiteralPath (Join-Path $marketplaceRoot 'LICENSE.txt') -Destination $stageDirectory
Copy-Item -LiteralPath (Join-Path $marketplaceRoot 'CHANGELOG.md') -Destination $stageDirectory

$previewDirectory = Join-Path $stageDirectory 'preview'
New-Item -ItemType Directory -Force -Path $previewDirectory | Out-Null
foreach ($previewName in @('home.png', 'collections.png', 'promise-solitaire.png', 'mobile-home.png')) {
    $previewSource = Join-Path $repoRoot ("artifacts\screenshots\{0}" -f $previewName)
    if (Test-Path -LiteralPath $previewSource) { Copy-Item -LiteralPath $previewSource -Destination $previewDirectory }
}

Compress-Archive -Path (Join-Path $stageDirectory '*') -DestinationPath $marketplaceArchive -CompressionLevel Optimal
Remove-Item -LiteralPath $stageDirectory -Recurse -Force

$hashes = @($installerArchive, $marketplaceArchive) | ForEach-Object {
    $stream = [System.IO.File]::OpenRead($_)
    $sha256 = [System.Security.Cryptography.SHA256]::Create()
    try {
        $digest = [System.BitConverter]::ToString($sha256.ComputeHash($stream)).Replace('-', '').ToLowerInvariant()
    } finally {
        $sha256.Dispose()
        $stream.Dispose()
    }
    "{0}  {1}" -f $digest, (Split-Path -Leaf $_)
}
Set-Content -LiteralPath (Join-Path $OutputDirectory 'SHA256SUMS.txt') -Value $hashes -Encoding utf8

Write-Output "OpenCart installer: $installerArchive"
Write-Output "Marketplace delivery bundle: $marketplaceArchive"
Write-Output "Version: $($manifest.version)"
