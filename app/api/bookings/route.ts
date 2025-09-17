import { NextResponse } from 'next/server';
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

export async function GET() {
  const bookings = await prisma.booking.findMany({ orderBy: { createdAt: 'desc' } });
  return NextResponse.json(bookings);
}

export async function POST(request: Request) {
  const data = await request.json();
  const booking = await prisma.booking.create({ data });
  return NextResponse.json(booking, { status: 201 });
}
