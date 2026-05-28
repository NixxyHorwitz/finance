import { NextResponse } from 'next/server';
import prisma from '@/lib/prisma';
import { getServerSession } from "next-auth/next";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";

export async function DELETE(request, { params }) {
  const session = await getServerSession(authOptions);
  if (!session) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const { id } = params;

  try {
    const transaction = await prisma.transaction.findUnique({
      where: { id }
    });

    if (!transaction || transaction.userId !== session.user.id) {
      return NextResponse.json({ error: "Transaction not found or unauthorized" }, { status: 404 });
    }

    // Revert wallet balances within transaction
    await prisma.$transaction(async (tx) => {
      if (transaction.type === "INCOME") {
        await tx.wallet.update({
          where: { id: transaction.walletId },
          data: { balance: { decrement: transaction.amount } }
        });
      } else if (transaction.type === "EXPENSE") {
        await tx.wallet.update({
          where: { id: transaction.walletId },
          data: { balance: { increment: transaction.amount } }
        });
      } else if (transaction.type === "TRANSFER") {
        await tx.wallet.update({
          where: { id: transaction.walletId },
          data: { balance: { increment: transaction.amount } }
        });
        await tx.wallet.update({
          where: { id: transaction.relatedWalletId },
          data: { balance: { decrement: transaction.amount } }
        });
      }

      await tx.transaction.delete({
        where: { id }
      });
    });

    return NextResponse.json({ message: "Transaction deleted successfully" });
  } catch (error) {
    console.error("Error deleting transaction:", error);
    return NextResponse.json({ error: "Internal Server Error" }, { status: 500 });
  }
}
