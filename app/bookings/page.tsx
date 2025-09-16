
import BookingForm from './form';

async function getBookings() {
  const res = await fetch('http://localhost:3000/api/bookings', { cache: 'no-store' });
  return res.json();
}

export default async function BookingsPage() {
  const bookings = await getBookings();
  return (
    <main className="max-w-2xl mx-auto py-10 px-4">
      <h1 className="text-2xl font-bold mb-4">Boekingen</h1>
      <BookingForm />
      <ul className="space-y-4 mt-8">
        {bookings.map((b: any) => (
          <li key={b.id} className="border rounded p-4 bg-white">
            <div><b>Naam:</b> {b.name}</div>
            <div><b>Email:</b> {b.email}</div>
            <div><b>Check-in:</b> {b.checkIn?.slice(0,10)}</div>
            <div><b>Check-out:</b> {b.checkOut?.slice(0,10)}</div>
            <div><b>Kamer:</b> {b.roomType}</div>
            <div className="text-xs text-gray-400">Aangemaakt: {b.createdAt?.slice(0,10)}</div>
          </li>
        ))}
      </ul>
    </main>
  );
}
