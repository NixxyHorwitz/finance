import "./globals.css";
import Providers from "@/components/Providers";

export const metadata = {
  title: "Neobrutalism Finance",
  description: "A compact, modern, and bold personal finance tracker.",
};

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>
        <Providers>
          {children}
        </Providers>
      </body>
    </html>
  );
}
