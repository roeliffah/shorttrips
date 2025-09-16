
"use client";
import { useState } from 'react';

export default function BookingForm() {
  const [form, setForm] = useState({ name: '', email: '', checkIn: '', checkOut: '', roomType: '' });
  const [success, setSuccess] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const res = await fetch('/api/bookings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    });
    if (res.ok) setSuccess(true);
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4 bg-white p-4 rounded shadow max-w-md mx-auto mt-8">
      <h2 className="text-xl font-bold mb-2">Nieuwe Boeking</h2>
      <input required className="border p-2 w-full" placeholder="Naam" value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} />
      <input required className="border p-2 w-full" placeholder="Email" type="email" value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} />
      <input required className="border p-2 w-full" placeholder="Check-in" type="date" value={form.checkIn} onChange={e => setForm(f => ({ ...f, checkIn: e.target.value }))} />
      <input required className="border p-2 w-full" placeholder="Check-out" type="date" value={form.checkOut} onChange={e => setForm(f => ({ ...f, checkOut: e.target.value }))} />
      <input required className="border p-2 w-full" placeholder="Kamertype" value={form.roomType} onChange={e => setForm(f => ({ ...f, roomType: e.target.value }))} />
      <button className="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Boek</button>
      {success && <div className="text-green-600 mt-2">Boeking succesvol!</div>}
    </form>
  );
}
