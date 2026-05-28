"use client";

import { useState, useEffect } from "react";
import { X } from "lucide-react";

export default function SetBalanceModal({ isOpen, onClose, wallet, onBalanceUpdate }) {
  const [balance, setBalance] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (wallet) {
      setBalance(wallet.balance.toString());
    }
  }, [wallet]);

  if (!isOpen || !wallet) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const res = await fetch("/api/wallets", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: wallet.id, balance: parseFloat(balance) })
      });

      if (res.ok) {
        onBalanceUpdate();
        onClose();
      } else {
        const data = await res.json();
        setError(data.error || "Failed to update balance");
      }
    } catch (err) {
      setError("An error occurred");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="neo-overlay">
      <div className="neo-modal" style={{ padding: '2rem' }}>
        <button 
          onClick={onClose} 
          className="neo-btn neo-btn-white" 
          style={{ position: 'absolute', top: '10px', right: '10px', padding: '0.25rem 0.5rem' }}
        >
          <X size={20} />
        </button>

        <h2 className="text-2xl text-bold mb-4">Set Balance</h2>
        <p className="mb-4 text-bold" style={{ color: 'var(--primary)' }}>Wallet: {wallet.name}</p>

        {error && (
          <div className="mb-4 p-2 text-bold" style={{ backgroundColor: 'var(--secondary)', border: '2px solid #000' }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="flex-col gap-4 flex">
          <div>
            <label className="text-bold mb-2" style={{ display: 'block' }}>New Balance</label>
            <input 
              type="number" 
              className="neo-input" 
              value={balance}
              onChange={(e) => setBalance(e.target.value)}
              min="0"
              required 
            />
          </div>

          <button type="submit" className="neo-btn mt-4" disabled={loading}>
            {loading ? "SAVING..." : "SAVE BALANCE"}
          </button>
        </form>
      </div>
    </div>
  );
}
