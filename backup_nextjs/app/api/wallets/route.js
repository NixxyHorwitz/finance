import { NextResponse } from 'next/server';
import prisma from '@/lib/prisma';
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";

export async function GET(request) {
  const session = await getServerSession(authOptions);
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const wallets = await prisma.wallet.findMany({
      where: { userId: session.user.id },
      orderBy: { name: 'asc' }
    });
    return NextResponse.json(wallets);
  } catch (error) {
    console.error("Error fetching wallets:", error);
    return NextResponse.json({ error: "Internal Server Error" }, { status: 500 });
  }
}

export async function PUT(request) {
  const session = await getServerSession(authOptions);
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const { id, balance } = await request.json();
    
    if (!id || balance === undefined) {
      return NextResponse.json({ error: "Missing id or balance" }, { status: 400 });
    }

    // Verify wallet belongs to user
    const wallet = await prisma.wallet.findUnique({
      where: { id }
    });

    if (!wallet || wallet.userId !== session.user.id) {
      return NextResponse.json({ error: "Wallet not found or unauthorized" }, { status: 404 });
    }

    const updatedWallet = await prisma.wallet.update({
      where: { id },
      data: { balance: parseFloat(balance) }
    });

    return NextResponse.json(updatedWallet);
  } catch (error) {
    console.error("Error updating wallet:", error);
    return NextResponse.json({ error: "Internal Server Error" }, { status: 500 });
  }
}
