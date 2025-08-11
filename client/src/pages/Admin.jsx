import React, { useState } from 'react';
import axios from 'axios';

function localDatetimeToISOString(localDatetime) {
  // localDatetime attendu : "YYYY-MM-DDTHH:MM" (value d'un <input type="datetime-local">)
  if (!localDatetime) return null;
  const [datePart, timePart] = localDatetime.split('T');
  if (!datePart || !timePart) return null;
  const [y, m, d] = datePart.split('-').map(Number);
  const [hh, mm] = timePart.split(':').map(Number);
  // crée Date en heure locale
  const dt = new Date(y, m - 1, d, hh, mm, 0, 0);
  return dt.toISOString(); // renvoie avec offset UTC
}

export default function Admin() {
  const [datetime, setDatetime] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage('');

    if (!datetime) {
      setMessage('❌ Choisissez une date et une heure.');
      return;
    }

    const isoDatetime = localDatetimeToISOString(datetime);
    if (!isoDatetime) {
      setMessage('❌ Format de date invalide.');
      return;
    }

    setLoading(true);
    try {
      // Utilise application/json pour éviter les subtilités JSON-LD
      await axios.post(
        'http://localhost:8000/api/slots',
        {
          datetime: isoDatetime,
          reserved: false
        },
        {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        }
      );

      setMessage('✅ Créneau ajouté avec succès');
      setDatetime('');
    } catch (err) {
      // Afficheer la cause si possible
      const serverMsg = err.response?.data?.detail || err.response?.data?.message || err.message;
      setMessage(`❌ Erreur : ${serverMsg}`);
      console.error(err.response || err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 p-6">
      <div className="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">
        <h2 className="text-xl font-semibold mb-4">Ajouter un créneau</h2>

        <form onSubmit={handleSubmit} className="space-y-4">
          <label className="block">
            <span className="text-sm font-medium text-gray-700">Date et heure</span>
            <input
              type="datetime-local"
              value={datetime}
              onChange={e => setDatetime(e.target.value)}
              className="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 focus:ring focus:ring-blue-200"
              required
            />
          </label>

          <button
            type="submit"
            disabled={loading}
            className={`w-full py-2 rounded-md font-medium ${
              loading ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'
            }`}
          >
            {loading ? 'Ajout en cours...' : 'Ajouter'}
          </button>
        </form>

        {message && (
          <div className={`mt-4 p-3 rounded ${message.startsWith('✅') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
            {message}
          </div>
        )}
      </div>
    </div>
  );
}
