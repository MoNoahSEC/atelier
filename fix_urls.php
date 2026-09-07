$assets = DB::table('media_assets')->where('url', 'like', 'http://%')->get();
$count = 0;
foreach ($assets as $asset) {
    // Strip the host prefix, keep /storage/... onwards
    $parsed = parse_url($asset->url);
    $relative = $parsed['path'];  // e.g. /storage/media/filename.jpg
    DB::table('media_assets')->where('id', $asset->id)->update(['url' => $relative]);
    $count++;
}
echo "Updated $count rows.";