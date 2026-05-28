"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";

export default function Register() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const res = await fetch("/api/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, password })
      });

      if (res.ok) {
        router.push("/login?registered=true");
      } else {
        const data = await res.json();
        setError(data.message || "Registration failed");
      }
    } catch (err) {
      setError("An error occurred");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container" style={{ display: 'flex', height: '100vh', alignItems: 'center', justifyContent: 'center' }}>
      <div className="neo-box" style={{ width: '100%', maxWidth: '400px' }}>
        <h1 className="text-3xl text-bold text-center mb-6" style={{ color: 'var(--primary)' }}>REGISTER</h1>
        
        {error && (
          <div className="neo-box mb-4" style={{ backgroundColor: 'var(--secondary)', padding: '0.75rem', border: '2px solid #000' }}>
            <p className="text-bold">{error}</p>
          </div>
        )}

        <form onSubmit={handleSubmit} className="flex-col gap-4 flex">
          <div>
            <label className="text-bold mb-2" style={{ display: 'block' }}>Username</label>
            <input 
              type="text" 
              className="neo-input" 
              placeholder="Enter username" 
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required 
            />
          </div>

          <div>
            <label className="text-bold mb-2" style={{ display: 'block' }}>Password</label>
            <input 
              type="password" 
              className="neo-input" 
              placeholder="Enter password" 
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required 
            />
          </div>

          <button type="submit" className="neo-btn" style={{ width: '100%', marginTop: '1rem' }} disabled={loading}>
            {loading ? "REGISTERING..." : "REGISTER"}
          </button>
        </form>

        <p className="text-center mt-6 text-bold">
          Already have an account? <Link href="/login" style={{ color: 'var(--secondary)' }}>Login here</Link>
        </p>
      </div>
    </div>
  );
}
