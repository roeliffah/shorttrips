"use client";
import { useEffect, useState } from "react";

const API_URL = "https://freestays.eu/api.php"; // Gebruik je externe API, niet localhost

export default function BookingsPage() {
  const [bookings, setBookings] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchBookings() {
      setLoading(true);
      setError(null);
      try {
        const res = await fetch(`${API_URL}?action=bookings&key=hlIGzfFEk5Af0dWNZO4p`);
        if (!res.ok) throw new Error("Fout bij ophalen boekingen");
        const data = await res.json();
        setBookings(data.results || []);
      } catch (e: any) {
        setError(e.message || "Onbekende fout");
      }
      setLoading(false);
    }
    fetchBookings();
  }, []);

  return (
    <main className="max-w-2xl mx-auto py-10 px-4">
      <h1 className="text-2xl font-bold mb-4">Mijn boekingen</h1>
      {loading && <div>Boekingen laden...</div>}
      {error && <div className="text-red-600">{error}</div>}
      {bookings.length > 0 ? (
        <ul className="space-y-4">
          {bookings.map((booking, i) => (
            <li key={i} className="border rounded p-4 bg-white shadow">
              <div><b>Hotel:</b> {booking.hotel_name}</div>
              <div><b>Check-in:</b> {booking.checkin}</div>
              <div><b>Check-out:</b> {booking.checkout}</div>
              {/* Voeg meer boekingsinfo toe indien gewenst */}
            </li>
          ))}
        </ul>
      ) : (
        !loading && <div className="text-gray-500">Geen boekingen gevonden.</div>
      )}
    </main>
  );
}
