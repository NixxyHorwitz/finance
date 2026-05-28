"use client";

import { useState } from "react";
import { X } from "lucide-react";

export default function TransactionModal({ isOpen, onClose, wallets, onTransactionSuccess }) {
  const [type, setType] = useState("EXPENSE"); // EXPENSE, INCOME, TRANSFER
  const [amount, setAmount] = useState("");
  const [description, setDescription] = useState("");
  const [walletId, setWalletId] = useState("");
  const [relatedWalletId, setRelatedWalletId] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const payload = {
        type,
        amount: parseFloat(amount),
        description,
        walletId
      };

      if (type === "TRANSFER") {
        payload.relatedWalletId = relatedWalletId;
        if (walletId === relatedWalletId) {
          setError("Cannot transfer to the same wallet");
          setLoading(false);
          return;
        }
      }

      const res = await fetch("/api/transactions", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        setAmount("");
        setDescription("");
        onTransactionSuccess();
        onClose();
      } else {
        const data = await res.json();
        setError(data.error || "Transaction failed");
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

        <h2 className="text-2xl text-bold mb-4">Add Transaction</h2>

        {error && (
          <div className="mb-4 p-2 text-bold" style={{ backgroundColor: 'var(--secondary)', border: '2px solid #000' }}>
            {error}
          </div>
        )}

        <div className="flex gap-2 mb-6">
          <button 
            type="button"
            className={`neo-btn ${type === 'EXPENSE' ? 'neo-btn-secondary' : 'neo-btn-white'}`}
            style={{ flex: 1, padding: '0.5rem' }}
            onClick={() => setType('EXPENSE')}
          >
            Expense
          </button>
          <button 
            type="button"
            className={`neo-btn ${type === 'INCOME' ? 'neo-btn-tertiary' : 'neo-btn-white'}`}
            style={{ flex: 1, padding: '0.5rem' }}
            onClick={() => setType('INCOME')}
          >
            Income
          </button>
          <button 
            type="button"
            className={`neo-btn ${type === 'TRANSFER' ? '' : 'neo-btn-white'}`}
            style={{ flex: 1, padding: '0.5rem', backgroundColor: type === 'TRANSFER' ? 'var(--primary)' : 'var(--white)' }}
            onClick={() => setType('TRANSFER')}
          >
            Transfer
          </button>
        </div>

        <form onSubmit={handleSubmit} className="flex-col gap-4 flex">
          <div>
            <label className="text-bold mb-2" style={{ display: 'block' }}>From Wallet</label>
            <select 
              className="neo-select" 
              value={walletId} 
              onChange={(e) => setWalletId(e.target.value)}
              required
            >
              <option value="" disabled>Select Wallet</option>
              {wallets.map(w => (
                <option key={w.id} value={w.id}>{w.name}</option>
              ))}
            </select>
          </div>

          {type === "TRANSFER" && (
            <div>
              <label className="text-bold mb-2" style={{ display: 'block' }}>To Wallet</label>
              <select 
                className="neo-select" 
                value={relatedWalletId} 
                onChange={(e) => setRelatedWalletId(e.target.value)}
                required
              >
                <option value="" disabled>Select Destination Wallet</option>
                {wallets.map(w => (
                  <option key={w.id} value={w.id}>{w.name}</option>
                ))}
              </select>
            </div>
          )}

          <div>
            <label className="text-bold mb-2" style={{ display: 'block' }}>Amount</label>
            <input 
              type="number" 
              className="neo-input" 
              placeholder="e.g. 50000" 
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
              min="1"
              required 
            />
          </div>

          <div>
            <label className="text-bold mb-2" style={{ display: 'block' }}>Description (Smart Category)</label>
            <input 
              type="text" 
              className="neo-input" 
              placeholder="e.g. Beli makan siang" 
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              required 
            />
            {type !== "TRANSFER" && (
              <p className="mt-2 text-sm" style={{ fontWeight: 600, color: '#555' }}>
                Tip: Category is automatically assigned based on keywords!
              </p>
            )}
          </div>

          <button type="submit" className="neo-btn mt-4" disabled={loading}>
            {loading ? "SAVING..." : "SAVE TRANSACTION"}
          </button>
        </form>
      </div>
    </div>
  );
}
