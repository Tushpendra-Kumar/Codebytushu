$files = Get-ChildItem -Path . -Recurse -Include *.html, *.php -Exclude vendor, node_modules

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    
    if ($content -match 'function closeDrawer\(\)') {
        if ($content -notmatch 'mobHamBtn\.focus\(\);') {
            $newContent = $content -replace "(mobHamBtn\.setAttribute\('aria-expanded',\s*'false'\);)", "`$1`n                    mobHamBtn.focus();"
            
            if ($newContent -notmatch "mobDrawer\.setAttribute\('aria-hidden',\s*'true'\);") {
                $newContent = $newContent -replace "(mobDrawer\.classList\.remove\('is-open'\);)", "`$1`n                mobDrawer.setAttribute('aria-hidden', 'true');"
            }
            
            Set-Content $file.FullName $newContent
            Write-Host "Updated: $($file.FullName)"
        }
    }
}
