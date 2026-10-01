# Builds the upload zips for a live WordPress site into dist/:
#   parish-cathedral.zip  (Appearance → Themes → Add Theme → Upload)
#   parish-core.zip       (Plugins → Add Plugin → Upload)
#   parish-setup.zip      (one-time content loader: tools/seed.php plus its photos)
# Run from the repo root:  powershell -File tools/build-zips.ps1

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
$root = Split-Path $PSScriptRoot -Parent
$dist = Join-Path $root 'dist'
New-Item -ItemType Directory -Force $dist | Out-Null

# Zip paths must use forward slashes, or WordPress on Linux rejects the archive.
function Add-Folder($zip, $src, $prefix) {
	Get-ChildItem $src -Recurse -File | ForEach-Object {
		$rel = $prefix + '/' + $_.FullName.Substring($src.Length + 1).Replace('\', '/')
		[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $rel)
	}
}

function New-Zip($name, [scriptblock]$fill) {
	$out = Join-Path $dist "$name.zip"
	if (Test-Path $out) { [System.IO.File]::Delete($out) }
	$zip = [System.IO.Compression.ZipFile]::Open($out, 'Create')
	try { & $fill $zip } finally { $zip.Dispose() }
	Write-Output "$out  ($([math]::Round((Get-Item $out).Length / 1KB)) KB)"
}

New-Zip 'parish-cathedral' { param($z) Add-Folder $z "$root\wp-content\themes\parish-cathedral" 'parish-cathedral' }
New-Zip 'parish-core' { param($z) Add-Folder $z "$root\wp-content\plugins\parish-core" 'parish-core' }
New-Zip 'parish-setup' {
	param($z)
	[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($z, "$root\tools\parish-setup\parish-setup.php", 'parish-setup/parish-setup.php')
	[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($z, "$root\tools\seed.php", 'parish-setup/seed/seed.php')
	Add-Folder $z "$root\tools\photos" 'parish-setup/seed/photos'
	Add-Folder $z "$root\tools\clergy" 'parish-setup/seed/clergy'
}
