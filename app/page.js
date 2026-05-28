"use client";

import { useSession, signOut } from "next-auth/react";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { LogOut, Eye, EyeOff, Plus, Trash2 } from "lucide-react";
import WalletCard from "@/components/WalletCard";
import TransactionModal from "@/components/TransactionModal";
import SetBalanceModal from "@/components/SetBalanceModal";
import FinanceChart from "@/components/Chart";

export default function Dashboard() {
  const { data: session, status } = useSession();
  const router = useRouter();
  
  const [wallets, setWallets] = useState([]);
  const [transactions, setTransactions] = useState([]);
  const [showBalance, setShowBalance] = useState(true);
  const [isTxModalOpen, setIsTxModalOpen] = useState(false);
  
  const [isBalanceModalOpen, setIsBalanceModalOpen] = useState(false);
  const [selectedWallet, setSelectedWallet] = useState(null);

  useEffect(() => {
    if (status === "unauthenticated") {
      router.push("/login");
    } else if (status === "authenticated") {
      fetchData();
    }
  }, [status, router]);

  const fetchData = async () => {
    try {
      const resW = await fetch("/api/wallets");
      if (resW.ok) setWallets(await resW.json());
      
      const resT = await fetch("/api/transactions");
      if (resT.ok) setTransactions(await resT.json());
    } catch (error) {
      console.error("Failed to fetch data", error);
    }
  };

  const handleDeleteTransaction = async (id) => {
    if (!confirm("Are you sure you want to delete this transaction? Balance will be adjusted back.")) return;
    try {
      const res = await fetch(`/api/transactions/${id}`, { method: 'DELETE' });
      if (res.ok) fetchData();
    } catch (err) {
      console.error("Delete failed", err);
    }
  };

  const handleEditBalance = (wallet) => {
    setSelectedWallet(wallet);
    setIsBalanceModalOpen(true);
  };

  if (status === "loading") {
    return <div className="container flex justify-center items-center h-[100vh] text-2xl text-bold">LOADING...</div>;
  }

  if (!session) return null;

  const totalBalance = wallets.reduce((acc, curr) => acc + curr.balance, 0);

  const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(number);
  };

  return (
    <div className="container">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-3xl text-bold" style={{ textTransform: 'uppercase' }}>Neofinance</h1>
          <p className="text-bold" style={{ color: '#555' }}>Welcome back, {session.user.name}</p>
        </div>
        <button onClick={() => signOut()} className="neo-btn neo-btn-white flex items-center gap-2" style={{ padding: '0.5rem 1rem' }}>
          <LogOut size={18} /> Logout
        </button>
      </div>

      {/* Total Balance */}
      <div className="neo-box mb-6 flex justify-between items-center" style={{ backgroundColor: 'var(--primary)' }}>
        <div>
          <p className="text-lg text-bold mb-2">Total Balance</p>
          <h2 className="text-3xl text-bold">
            {showBalance ? formatRupiah(totalBalance) : "Rp •••••••••"}
          </h2>
        </div>
        <button 
          onClick={() => setShowBalance(!showBalance)} 
          className="neo-btn neo-btn-white" 
          style={{ padding: '0.5rem', borderRadius: '50%' }}
        >
          {showBalance ? <EyeOff size={24} /> : <Eye size={24} />}
        </button>
      </div>

      {/* Wallets Grid */}
      <h2 className="text-2xl text-bold mb-4">My Wallets</h2>
      <div className="wallet-grid mb-8">
        {wallets.map(w => (
          <WalletCard 
            key={w.id} 
            wallet={w} 
            showBalance={showBalance} 
            onEditBalance={handleEditBalance}
          />
        ))}
      </div>

      {/* Action Bar */}
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl text-bold">Transactions & Analytics</h2>
        <button onClick={() => setIsTxModalOpen(true)} className="neo-btn neo-btn-tertiary flex items-center gap-2">
          <Plus size={20} /> Add Record
        </button>
      </div>

      {/* Chart */}
      <div className="mb-8">
        <FinanceChart transactions={transactions} />
      </div>

      {/* Recent Transactions */}
      <div className="neo-box">
        <h3 className="text-xl text-bold mb-4">Recent History</h3>
        {transactions.length === 0 ? (
          <p className="text-center text-bold" style={{ color: '#555' }}>No transactions found.</p>
        ) : (
          <div className="flex-col gap-4 flex">
            {transactions.map(t => (
              <div key={t.id} className="flex justify-between items-center" style={{ borderBottom: '2px solid #000', paddingBottom: '0.5rem' }}>
                <div>
                  <p className="text-bold">{t.description}</p>
                  <p className="text-sm" style={{ fontWeight: 600, color: '#555' }}>
                    {new Date(t.date).toLocaleDateString('id-ID')} • 
                    {t.type === 'TRANSFER' 
                      ? ` ${t.wallet.name} ➔ ${t.relatedWallet?.name}`
                      : ` ${t.wallet.name}`
                    }
                  </p>
                  {t.categoryId && t.type !== 'TRANSFER' && (
                    <span className="badge mt-1" style={{ backgroundColor: 'var(--white)' }}>
                      {t.categoryId}
                    </span>
                  )}
                </div>
                <div className="flex items-center gap-4">
                  <span className={`text-xl ${t.type === 'INCOME' ? 'amount-income' : t.type === 'EXPENSE' ? 'amount-expense' : 'text-bold'}`}>
                    {t.type === 'EXPENSE' ? '-' : t.type === 'INCOME' ? '+' : ''}
                    {formatRupiah(t.amount)}
                  </span>
                  <button 
                    onClick={() => handleDeleteTransaction(t.id)} 
                    className="neo-btn neo-btn-secondary"
                    style={{ padding: '0.25rem' }}
                    title="Delete"
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Modals */}
      <TransactionModal 
        isOpen={isTxModalOpen} 
        onClose={() => setIsTxModalOpen(false)} 
        wallets={wallets}
        onTransactionSuccess={fetchData}
      />

      <SetBalanceModal
        isOpen={isBalanceModalOpen}
        onClose={() => setIsBalanceModalOpen(false)}
        wallet={selectedWallet}
        onBalanceUpdate={fetchData}
      />
    </div>
  );
}
