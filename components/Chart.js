"use client";

import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

export default function FinanceChart({ transactions }) {
  // Process transactions into daily aggregates
  const dataMap = {};

  transactions.forEach(t => {
    if (t.type === "TRANSFER") return; // Ignore transfers for income/expense chart
    
    const date = new Date(t.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    
    if (!dataMap[date]) {
      dataMap[date] = { name: date, Income: 0, Expense: 0 };
    }
    
    if (t.type === "INCOME") {
      dataMap[date].Income += t.amount;
    } else if (t.type === "EXPENSE") {
      dataMap[date].Expense += t.amount;
    }
  });

  const data = Object.values(dataMap).reverse(); // Reverse to chronological if list is descending

  if (data.length === 0) {
    return (
      <div className="neo-box flex justify-center items-center" style={{ height: '300px' }}>
        <p className="text-bold text-lg">No chart data available</p>
      </div>
    );
  }

  return (
    <div className="neo-box" style={{ width: '100%', height: '350px', padding: '1rem' }}>
      <h3 className="text-xl text-bold mb-4">Financial Overview</h3>
      <ResponsiveContainer width="100%" height="85%">
        <BarChart
          data={data}
          margin={{ top: 5, right: 0, left: 20, bottom: 5 }}
        >
          <CartesianGrid strokeDasharray="3 3" stroke="#000" vertical={false} />
          <XAxis 
            dataKey="name" 
            tick={{ fill: '#000', fontWeight: 'bold', fontFamily: 'Outfit' }} 
            axisLine={{ stroke: '#000', strokeWidth: 2 }}
          />
          <YAxis 
            tick={{ fill: '#000', fontWeight: 'bold', fontFamily: 'Outfit' }} 
            axisLine={{ stroke: '#000', strokeWidth: 2 }}
            tickFormatter={(value) => `Rp${value / 1000}k`}
          />
          <Tooltip 
            cursor={{ fill: 'rgba(0,0,0,0.1)' }}
            contentStyle={{ border: '3px solid #000', borderRadius: 0, boxShadow: '4px 4px 0 #000', fontWeight: 'bold' }}
          />
          <Legend wrapperStyle={{ fontWeight: 'bold', paddingTop: '10px' }} />
          <Bar dataKey="Income" fill="var(--tertiary)" stroke="#000" strokeWidth={2} />
          <Bar dataKey="Expense" fill="var(--secondary)" stroke="#000" strokeWidth={2} />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}
