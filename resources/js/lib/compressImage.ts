export interface CompressOptions {
    maxWidth?: number;
    maxHeight?: number;
    maxSizeKb?: number;
    qualityStart?: number;
    qualityStep?: number;
    minQuality?: number;
}

export async function compressToWebp(
    file: File,
    options: CompressOptions = {},
): Promise<File> {
    const {
        maxWidth = 1280,
        maxHeight = 1280,
        maxSizeKb = 300,
        qualityStart = 0.9,
        qualityStep = 0.05,
        minQuality = 0.4,
    } = options;

    // Load image
    const img = await createImageBitmap(file);

    // Calculate new dimensions
    let { width, height } = img;
    if (width > maxWidth || height > maxHeight) {
        const ratio = Math.min(maxWidth / width, maxHeight / height);
        width = Math.round(width * ratio);
        height = Math.round(height * ratio);
    }

    // Create canvas
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        throw new Error('Gagal membuat canvas untuk kompresi gambar');
    }

    ctx.drawImage(img, 0, 0, width, height);

    // Try to compress
    let quality = qualityStart;
    let blob: Blob | null = null;

    while (quality >= minQuality) {
        blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, 'image/webp', quality),
        );

        if (!blob) {
            throw new Error('Gagal mengubah gambar ke WebP');
        }

        if (blob.size <= maxSizeKb * 1024) {
            break;
        }

        quality -= qualityStep;
    }

    if (!blob) {
        throw new Error('Gagal menghasilkan WebP dengan kualitas yang sesuai');
    }

    // If still too large, try smaller dimensions as fallback
    if (blob.size > maxSizeKb * 1024 && (width > 640 || height > 640)) {
        // Try once more with smaller size
        const ratio = 0.7;
        canvas.width = Math.round(width * ratio);
        canvas.height = Math.round(height * ratio);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, 'image/webp', 0.6),
        );
        if (!blob) {
            throw new Error('Gagal mengubah gambar ke WebP');
        }
    }

    // Create new file
    const originalName = file.name.replace(/\.[^/.]+$/, '');
    return new File([blob], `${originalName}.webp`, {
        type: 'image/webp',
        lastModified: Date.now(),
    });
}
