'use client';

import React, { useRef, useState } from 'react';
import Cropper, { ReactCropperElement } from 'react-cropper';
import 'cropperjs/dist/cropper.css';

interface ImageEditorModalProps {
  isOpen: boolean;
  onClose: () => void;
  imageSrc: string;
  onConfirm: (file: File) => void;
}

const RATIOS = [
  { label: 'Free', value: NaN },
  { label: '1:1 Square', value: 1 },
  { label: '4:5 Portrait', value: 4 / 5 },
  { label: '4:3 Landscape', value: 4 / 3 },
  { label: '16:9 Wide', value: 16 / 9 },
];

export function ImageEditorModal({ isOpen, onClose, imageSrc, onConfirm }: ImageEditorModalProps) {
  const cropperRef = useRef<ReactCropperElement>(null);
  const [aspectRatio, setAspectRatio] = useState<number>(NaN);
  const [processing, setProcessing] = useState(false);

  if (!isOpen) return null;

  const handleConfirm = () => {
    if (!cropperRef.current) return;
    const cropper = cropperRef.current.cropper;
    if (!cropper) return;

    setProcessing(true);
    cropper.getCroppedCanvas({
      maxWidth: 2048,
      maxHeight: 2048,
    }).toBlob((blob) => {
      if (blob) {
        const file = new File([blob], 'cropped-image.jpg', { type: 'image/jpeg' });
        onConfirm(file);
      }
      setProcessing(false);
    }, 'image/jpeg', 0.9);
  };

  const handleRotate = (degree: number) => {
    if (cropperRef.current?.cropper) cropperRef.current.cropper.rotate(degree);
  };

  const handleZoom = (ratio: number) => {
    if (cropperRef.current?.cropper) cropperRef.current.cropper.zoom(ratio);
  };

  const handleReset = () => {
    if (cropperRef.current?.cropper) cropperRef.current.cropper.reset();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
      <div className="bg-[#111] border border-white/10 w-full max-w-4xl flex flex-col max-h-[90vh]">
        {/* Header */}
        <div className="px-6 py-4 border-b border-white/10 flex items-center justify-between">
          <h2 className="text-white font-mono uppercase tracking-widest text-xs">Edit Image</h2>
          <button onClick={onClose} className="text-white/50 hover:text-white">&times; Close</button>
        </div>

        {/* Editor Body */}
        <div className="flex-1 overflow-hidden p-6 flex flex-col md:flex-row gap-6">
          {/* Main Cropper Area */}
          <div className="flex-1 min-h-[300px] md:min-h-[500px] bg-black border border-white/5 relative">
            <Cropper
              src={imageSrc}
              style={{ height: '100%', width: '100%' }}
              initialAspectRatio={NaN}
              aspectRatio={aspectRatio}
              guides={true}
              ref={cropperRef}
              viewMode={1}
              background={false}
              responsive={true}
              checkOrientation={false}
            />
          </div>

          {/* Controls Sidebar */}
          <div className="w-full md:w-64 flex flex-col gap-6 shrink-0 overflow-y-auto">
            <div>
              <label className="block text-[10px] uppercase tracking-[0.2em] text-white/40 mb-3 font-mono">Aspect Ratio</label>
              <div className="flex flex-col gap-2">
                {RATIOS.map((r) => (
                  <button
                    key={r.label}
                    onClick={() => setAspectRatio(r.value)}
                    className={`px-3 py-2 text-xs font-mono uppercase tracking-wider text-left border transition-colors ${
                      (Number.isNaN(aspectRatio) && Number.isNaN(r.value)) || aspectRatio === r.value
                        ? 'border-[#e8ff47] text-[#e8ff47]'
                        : 'border-white/10 text-white/60 hover:text-white'
                    }`}
                  >
                    {r.label}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <label className="block text-[10px] uppercase tracking-[0.2em] text-white/40 mb-3 font-mono">Transform</label>
              <div className="grid grid-cols-2 gap-2">
                <button onClick={() => handleRotate(-90)} className="px-3 py-2 text-xs border border-white/10 text-white/60 hover:text-white hover:border-white/30 transition-colors">↺ Rotate L</button>
                <button onClick={() => handleRotate(90)} className="px-3 py-2 text-xs border border-white/10 text-white/60 hover:text-white hover:border-white/30 transition-colors">↻ Rotate R</button>
                <button onClick={() => handleZoom(0.1)} className="px-3 py-2 text-xs border border-white/10 text-white/60 hover:text-white hover:border-white/30 transition-colors">+ Zoom In</button>
                <button onClick={() => handleZoom(-0.1)} className="px-3 py-2 text-xs border border-white/10 text-white/60 hover:text-white hover:border-white/30 transition-colors">- Zoom Out</button>
              </div>
              <button onClick={handleReset} className="mt-2 w-full px-3 py-2 text-xs border border-white/10 text-white/60 hover:text-white hover:border-white/30 transition-colors">Reset All</button>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="px-6 py-4 border-t border-white/10 flex justify-end gap-3 bg-black/20">
          <button
            onClick={onClose}
            className="px-6 py-3 font-mono uppercase tracking-widest text-xs text-white/60 hover:text-white transition-colors border border-transparent hover:border-white/20"
          >
            Cancel
          </button>
          <button
            onClick={handleConfirm}
            disabled={processing}
            className="px-8 py-3 bg-[#e8ff47] text-black font-mono font-bold uppercase tracking-widest text-xs hover:bg-white transition-colors disabled:opacity-50"
          >
            {processing ? 'Processing...' : 'Confirm & Upload'}
          </button>
        </div>
      </div>
    </div>
  );
}
