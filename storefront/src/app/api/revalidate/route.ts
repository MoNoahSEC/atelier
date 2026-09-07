import { NextRequest, NextResponse } from 'next/server';

/**
 * POST /api/revalidate
 * Called by Laravel when a CMS page is published.
 * Body: { path: '/homepage', secret: '...' }
 */
export async function POST(request: NextRequest) {
  const secret = process.env.NEXTJS_REVALIDATE_SECRET;

  const body = await request.json().catch(() => ({}));

  if (!secret || body.secret !== secret) {
    return NextResponse.json({ success: false, message: 'Invalid secret' }, { status: 401 });
  }

  const path = body.path as string | undefined;
  if (!path) {
    return NextResponse.json({ success: false, message: 'Missing path' }, { status: 400 });
  }

  try {
    const { revalidatePath } = await import('next/cache');
    revalidatePath(path);
    console.log(`[Revalidation] Revalidated: ${path}`);
    return NextResponse.json({ success: true, revalidated: path });
  } catch (err) {
    console.error(`[Revalidation] Failed for ${path}:`, err);
    return NextResponse.json({ success: false, message: 'Revalidation failed' }, { status: 500 });
  }
}
