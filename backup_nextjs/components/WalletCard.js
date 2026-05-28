"use client";

import { useState } from "react";
import Image from "next/image";
import { Wallet, PiggyBank, Pencil } from "lucide-react";

export default function WalletCard({ wallet, onEditBalance, showBalance }) {
  const [isHovered, setIsHovered] = useState(false);

  const getLogo = (name) => {
    const lowerName = name.toLowerCase();
    if (lowerName.includes("dana")) return <Image src="/dana.png" alt="Dana" width={40} height={40} className="logo-icon" />;
    if (lowerName.includes("gopay")) return <Image src="/gopay.png" alt="Gopay" width={40} height={40} className="logo-icon" />;
    if (lowerName.includes("shopee")) return <Image src="/shopeepay.png" alt="ShopeePay" width={40} height={40} className="logo-icon" />;
    if (lowerName.includes("saving")) return <div className="logo-icon flex justify-center items-center" style={{ backgroundColor: 'var(--tertiary)' }}><PiggyBank size={24} /></div>;
    return <div className="logo-icon flex justify-center items-center" style={{ backgroundColor: 'var(--primary)' }}><Wallet size={24} /></div>; // Default for Cash
  };

  const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0
    }).format(number);
  };

  return (
    <div 
      className="neo-box flex-col justify-between" 
      style={{ 
        position: 'relative', 
        height: '140px',
        backgroundColor: isHovered ? 'var(--white)' : 'var(--bg)',
        transform: isHovered ? 'translate(-2px, -2px)' : 'none',
        boxShadow: isHovered ? '6px 6px 0px 0px #000' : 'var(--shadow)',
        cursor: 'pointer'
      }}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div className="flex justify-between items-center mb-4">
        {getLogo(wallet.name)}
        <span className="text-bold text-lg">{wallet.name}</span>
      </div>
      
      <div className="flex justify-between items-end">
        <div>
          <p className="text-sm" style={{ fontWeight: 600 }}>Balance</p>
          <p className="text-xl text-bold">
            {showBalance ? formatRupiah(wallet.balance) : "Rp •••••••"}
          </p>
        </div>
        
        <button 
          onClick={() => onEditBalance(wallet)}
          className="neo-btn neo-btn-white"
          style={{ padding: '0.25rem 0.5rem', fontSize: '0.8rem' }}
          title="Set Balance"
        >
          <Pencil size={14} />
        </button>
      </div>
    </div>
  );
}
