"use client";

import { useState, useEffect, Suspense } from "react";
import { signIn } from "next-auth/react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";

function LoginContent() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false);
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    if (searchParams.get("registered")) {
      setMessage("Registration successful! Please login.");
    }
  }, [searchParams]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const res = await signIn("credentials", {
        redirect: false,
        username,
        password
      });

      if (res.error) {
        setError("Invalid username or password");
      } else {
        router.push("/");
        router.refresh(); // Ensure layout updates with session
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
        <h1 className="text-3xl text-bold text-center mb-6" style={{ color: 'var(--tertiary)' }}>LOGIN</h1>
        
        {message && (
          <div className="neo-box mb-4" style={{ backgroundColor: 'var(--primary)', padding: '0.75rem', border: '2px solid #000' }}>
            <p className="text-bold">{message}</p>
          </div>
        )}
        
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
            {loading ? "LOGGING IN..." : "LOGIN"}
          </button>
        </form>

        <p className="text-center mt-6 text-bold">
          Don't have an account? <Link href="/register" style={{ color: 'var(--primary)' }}>Register here</Link>
        </p>
      </div>
    </div>
  );
}

export default function Login() {
  return (
    <Suspense fallback={<div className="container flex justify-center items-center h-[100vh] text-2xl text-bold">LOADING...</div>}>
      <LoginContent />
    </Suspense>
  );
}
