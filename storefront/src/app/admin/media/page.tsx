'use client';
import { useEffect, useState, useRef } from 'react';
import { getMedia, uploadMedia, deleteMedia, updateMediaFocalPoint, updateMediaCropMode, type MediaAsset } from '@/lib/admin-api';


export default function AdminMediaPage() {
  const [media, setMedia] = useState<MediaAsset[]>([]);

  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [deleting, setDeleting] = useState<string | null>(null);
  const [error, setError] = useState('');
  const [selectedMedia, setSelectedMedia] = useState<string | null>(null);
  const [focalPointEditing, setFocalPointEditing] = useState<string | null>(null);
  const [cropModeUpdating, setCropModeUpdating] = useState<string | null>(null);
  const fileRef = useRef<HTMLInputElement>(null);
  const focalPointCanvasRef = useRef<HTMLCanvasElement>(null);

  async function load() {
    setLoading(true);
    try {
      const r = await getMedia();
      setMedia(r || []);
    } catch (e: any) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  async function handleUpload(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (!file) return;
    const isModelOrVideo = file.name.endsWith('.glb') || file.name.endsWith('.gltf') || file.name.endsWith('.mp4') || file.name.endsWith('.webm');
    if (!file.type.startsWith('image/') && !file.type.startsWith('video/') && !isModelOrVideo) {
      setError('Only images, videos (MP4/WebM), and 3D models (.glb/.gltf) are allowed.');
      return;
    }
    
    setUploading(true);
    setError('');
    try {
      await uploadMedia(file);
      await load();
    } catch (e: any) {
      setError(e.message);
    } finally {
      setUploading(false);
      if (fileRef.current) fileRef.current.value = '';
    }
  }

  async function handleDelete(filename: string) {
    if (!confirm(`Delete ${filename}? This action cannot be undone and may break images currently in use.`)) return;
    setDeleting(filename);
    try {
      await deleteMedia(filename);
      setMedia(m => m.filter(x => x.filename !== filename));
      setSelectedMedia(null);
    } catch (e: any) {
      setError(e.message);
    } finally {
      setDeleting(null);
    }
  }

  async function handleFocalPointSet(filename: string, x: number, y: number) {
    setFocalPointEditing(filename);
    try {
      const updated = await updateMediaFocalPoint(filename, x, y);
      setMedia(m => m.map(asset => asset.filename === filename ? updated : asset));
      setError('');
    } catch (e: any) {
      setError(e.message);
    } finally {
      setFocalPointEditing(null);
    }
  }

  async function handleCropModeSet(filename: string, newMode: 'cover' | 'contain') {
    const asset = media.find(m => m.filename === filename);
    if (!asset || asset.type !== 'image') return;

    const currentMode = (asset.metadata?.cropMode as string) || 'cover';
    // Bail out early if already in the requested mode
    if (currentMode === newMode) return;

    setCropModeUpdating(filename);
    try {
      const updated = await updateMediaCropMode(filename, newMode);
      setMedia(m => m.map(a => a.filename === filename ? updated : a));
      setError('');
    } catch (e: any) {
      setError(e.message);
    } finally {
      setCropModeUpdating(null);
    }
  }

  const selectedAsset = media.find(m => m.filename === selectedMedia);
  const focalPoint = selectedAsset?.metadata?.focalPoint as string || '50% 50%';
  const cropMode = (selectedAsset?.metadata?.cropMode as string) || 'cover';

  return (
    <div className="space-y-6 max-w-7xl">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="font-display font-black uppercase text-2xl tracking-tight text-stone-900">Media Library</h1>
          <p className="text-xs text-stone-500 mt-1">{media.length} media assets uploaded</p>
        </div>
        <div>
          <input ref={fileRef} type="file" accept="image/*,video/mp4,video/webm,.glb,.gltf" className="hidden" onChange={handleUpload} />
          <button 
            onClick={() => fileRef.current?.click()}
            disabled={uploading}
            className="bg-[#c2410c] text-amber-50 font-display font-bold uppercase text-xs tracking-wider px-5 py-3 rounded-xl hover:bg-[#9a3412] shadow-xs active:scale-95 transition-all disabled:opacity-50 cursor-pointer"
          >
            {uploading ? 'Uploading...' : '+ Upload Media'}
          </button>
        </div>
      </div>

      {error && <div className="px-4 py-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-display font-bold uppercase tracking-wider">{error}</div>}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Media Grid */}
        <div className="lg:col-span-2">
          {loading ? (
            <div className="flex items-center justify-center h-64 text-xs text-stone-400 font-display uppercase tracking-widest animate-pulse font-semibold">Loading media library...</div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
              {media.map((m) => (
                <div 
                  key={m.filename} 
                  onClick={() => m.type === 'image' && setSelectedMedia(m.filename)}
                  className={`group relative border rounded-2xl aspect-square flex items-center justify-center overflow-hidden cursor-pointer transition-all ${
                    selectedMedia === m.filename 
                      ? 'border-[#c2410c] bg-amber-50/50 shadow-md ring-2 ring-[#c2410c]/20' 
                      : 'border-stone-200 bg-white hover:border-stone-400 shadow-xs'
                  }`}
                >
                  <img 
                    src={m.url} 
                    alt={m.filename} 
                    className={`w-full h-full p-2 ${m.type === 'image' ? 'object-contain' : 'object-cover'}`}
                    onError={e => { (e.target as HTMLImageElement).style.display = 'none'; }}
                  />
                  {m.type !== 'image' && (
                    <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                      <span className="text-2xl">{m.type === 'video' ? '🎬' : '📦'}</span>
                      <span className="text-[10px] text-stone-600 font-mono uppercase mt-1 font-bold">{m.type}</span>
                    </div>
                  )}

                  <div className="absolute inset-0 bg-stone-900/80 rounded-2xl flex flex-col items-center justify-center p-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    <p className="text-[10px] text-stone-200 font-mono truncate w-full text-center mb-1 font-bold">{m.filename}</p>
                    <p className="text-[10px] text-stone-400 font-mono mb-4">{(m.size_bytes / 1024).toFixed(1)} KB</p>
                    
                    <div className="flex gap-2">
                      <button 
                        onClick={(e) => { e.stopPropagation(); navigator.clipboard.writeText(m.url); alert('URL Copied!'); }}
                        className="bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                        title="Copy URL"
                      >
                        Copy URL
                      </button>
                      <button 
                        onClick={(e) => { e.stopPropagation(); handleDelete(m.filename); }}
                        disabled={deleting === m.filename}
                        className="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors disabled:opacity-30 cursor-pointer"
                        title="Delete"
                      >
                        {deleting === m.filename ? '...' : 'Delete'}
                      </button>
                    </div>
                  </div>
                </div>
              ))}
              {!media.length && (
                <div className="col-span-full border-2 border-stone-200 border-dashed rounded-2xl p-16 flex flex-col items-center justify-center text-center">
                  <p className="text-stone-500 font-display uppercase tracking-wider text-xs font-bold">No media uploaded yet</p>
                  <button 
                    onClick={() => fileRef.current?.click()}
                    className="mt-4 text-[#c2410c] font-display text-xs uppercase tracking-wider font-bold hover:underline cursor-pointer"
                  >
                    Upload your first photo
                  </button>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Image Settings Panel */}
        {selectedAsset && selectedAsset.type === 'image' && (
          <div className="border border-stone-200 bg-white rounded-2xl p-6 space-y-6 h-fit sticky top-6 shadow-xs">
            <div>
              <h3 className="font-display font-bold text-xs uppercase tracking-wider text-stone-800 mb-1">Image Settings</h3>
              <p className="text-xs text-stone-500 font-mono truncate">{selectedAsset.filename}</p>
            </div>

            {/* Focal Point Editor */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <label className="text-xs font-display uppercase tracking-wider text-stone-700 font-bold">Focal Point</label>
                <span className="text-[10px] text-stone-500 font-mono font-bold">{focalPoint}</span>
              </div>
              <div className="relative border border-stone-200 bg-stone-50 rounded-xl aspect-video overflow-hidden">
                <img 
                  src={selectedAsset.url} 
                  alt="focal point"
                  className="w-full h-full object-contain"
                />
                {/* Focal point crosshair */}
                <div 
                  className="absolute w-8 h-8 border-2 border-[#c2410c] rounded-full pointer-events-none transform -translate-x-1/2 -translate-y-1/2 shadow-sm"
                  style={{ 
                    left: focalPoint.split('%')[0] + '%',
                    top: focalPoint.split(' ')[1].split('%')[0] + '%'
                  }}
                >
                  <div className="absolute inset-1/2 w-1.5 h-1.5 bg-[#c2410c] rounded-full transform -translate-x-1/2 -translate-y-1/2" />
                </div>
                {/* Clickable overlay */}
                <div 
                  className="absolute inset-0 cursor-crosshair"
                  onClick={(e) => {
                    if (focalPointEditing) return;
                    const rect = e.currentTarget.getBoundingClientRect();
                    const x = ((e.clientX - rect.left) / rect.width) * 100;
                    const y = ((e.clientY - rect.top) / rect.height) * 100;
                    handleFocalPointSet(selectedAsset.filename, Math.round(x), Math.round(y));
                  }}
                />
              </div>
              <p className="text-[11px] text-stone-500">Click on the image to set the auto-crop center point.</p>
            </div>

            {/* Crop Mode Toggle */}
            <div className="space-y-3">
              <label className="text-xs font-display uppercase tracking-wider text-stone-700 font-bold">Crop Mode</label>
              <div className="flex gap-2">
                {(['cover', 'contain'] as const).map((mode) => (
                  <button
                    key={mode}
                    onClick={() => handleCropModeSet(selectedAsset.filename, mode)}
                    disabled={cropModeUpdating === selectedAsset.filename || cropMode === mode}
                    className={`flex-1 px-4 py-2.5 rounded-xl text-xs font-display uppercase tracking-wider font-bold transition-all cursor-pointer ${
                      cropMode === mode
                        ? 'bg-[#c2410c] text-amber-50 shadow-xs'
                        : 'bg-stone-100 text-stone-700 hover:bg-stone-200'
                    } disabled:opacity-60`}
                  >
                    {cropModeUpdating === selectedAsset.filename && cropMode !== mode ? '...' : mode}
                  </button>
                ))}
              </div>
              <p className="text-[11px] text-stone-500">
                {cropMode === 'cover' 
                  ? 'Image fills card container smoothly.' 
                  : 'Entire image fits in container without cropping.'}
              </p>
            </div>
          </div>
        )}

        {/* No selection message */}
        {!selectedMedia && (
          <div className="border border-stone-200 bg-white rounded-2xl p-6 flex flex-col items-center justify-center text-center h-64 shadow-xs">
            <p className="text-xs text-stone-500 font-display uppercase tracking-wider font-semibold">Select an image to preview details or set focal points.</p>
          </div>
        )}
      </div>
    </div>
  );
}
