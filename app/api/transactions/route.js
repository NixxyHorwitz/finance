import { NextResponse } from 'next/server';
import prisma from '@/lib/prisma';
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";

// Simple smart category engine
const smartCategorize = (description) => {
  const desc = description.toLowerCase();
  const rules = [
    { category: "Food & Beverage", keywords: ["makan", "minum", "kopi", "cafe", "roti", "snack", "gofood", "grabfood", "shopeefood", "resto", "warteg", "nasi"] },
    { category: "Transportation", keywords: ["bensin", "parkir", "tol", "gojek", "grab", "maxim", "kereta", "krl", "bus", "tiket"] },
    { category: "Shopping", keywords: ["belanja", "baju", "sepatu", "shopee", "tokopedia", "indomaret", "alfamart", "supermarket"] },
    { category: "Entertainment", keywords: ["nonton", "bioskop", "game", "steam", "netflix", "spotify", "main"] },
    { category: "Bills", keywords: ["listrik", "air", "internet", "wifi", "pulsa", "kuota", "cicilan", "kos", "sewa"] },
    { category: "Income", keywords: ["gaji", "bonus", "thr", "cair", "jual", "untung"] }
  ];

  for (const rule of rules) {
    if (rule.keywords.some(kw => desc.includes(kw))) {
      return rule.category;
    }
  }
  return "Lainnya";
};

export async function GET(request) {
  const session = await getServerSession(authOptions);
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const transactions = await prisma.transaction.findMany({
      where: { userId: session.user.id },
      orderBy: { date: 'desc' },
      include: {
        wallet: true,
        relatedWallet: true
      }
    });
    return NextResponse.json(transactions);
  } catch (error) {
    console.error("Error fetching transactions:", error);
    return NextResponse.json({ error: "Internal Server Error" }, { status: 500 });
  }
}

export async function POST(request) {
  const session = await getServerSession(authOptions);
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    const body = await request.json();
    const { type, amount, description, walletId, relatedWalletId } = body;

    if (!type || !amount || !description || !walletId) {
      return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
    }

    const parsedAmount = parseFloat(amount);
    if (isNaN(parsedAmount) || parsedAmount <= 0) {
      return NextResponse.json({ error: "Invalid amount" }, { status: 400 });
    }

    // Verify wallet ownership
    const wallet = await prisma.wallet.findUnique({
      where: { id: walletId }
    });

    if (!wallet || wallet.userId !== session.user.id) {
      return NextResponse.json({ error: "Wallet not found or unauthorized" }, { status: 404 });
    }

    // Smart categorization
    let categoryName = null;
    if (type === "EXPENSE" || type === "INCOME") {
      categoryName = smartCategorize(description);
      
      // Ensure category exists in DB (upsert)
      const category = await prisma.category.upsert({
        where: { name: categoryName },
        update: {},
        create: { name: categoryName, keywords: "" }
      });
      categoryName = category.id;
    }

    // Execute transaction within Prisma Transaction (All or Nothing)
    const result = await prisma.$transaction(async (tx) => {
      // 1. Create Transaction Record
      const newTransaction = await tx.transaction.create({
        data: {
          userId: session.user.id,
          walletId,
          type,
          amount: parsedAmount,
          description,
          categoryId: categoryName,
          relatedWalletId: type === "TRANSFER" ? relatedWalletId : null
        }
      });

      // 2. Update Wallet Balance
      if (type === "INCOME") {
        await tx.wallet.update({
          where: { id: walletId },
          data: { balance: { increment: parsedAmount } }
        });
      } else if (type === "EXPENSE") {
        await tx.wallet.update({
          where: { id: walletId },
          data: { balance: { decrement: parsedAmount } }
        });
      } else if (type === "TRANSFER") {
        if (!relatedWalletId) throw new Error("Destination wallet is required for transfer");
        
        // Deduct from source
        await tx.wallet.update({
          where: { id: walletId },
          data: { balance: { decrement: parsedAmount } }
        });
        
        // Add to destination
        await tx.wallet.update({
          where: { id: relatedWalletId },
          data: { balance: { increment: parsedAmount } }
        });
      }

      return newTransaction;
    });

    return NextResponse.json(result, { status: 201 });
  } catch (error) {
    console.error("Error creating transaction:", error);
    return NextResponse.json({ error: error.message || "Internal Server Error" }, { status: 500 });
  }
}
