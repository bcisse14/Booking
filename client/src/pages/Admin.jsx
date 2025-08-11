import React, { useState } from 'react';
import axios from 'axios';

function localDatetimeToISOString(localDatetime) {
  if (!localDatetime) return null;
  const [datePart, timePart] = localDatetime.split('T');
  if (!datePart || !timePart) return null;
  const [y, m, d] = datePart.split('-').map(Number);
  const [hh, mm] = timePart.split(':').map(Number);
  const dt = new Date(y, m - 1, d, hh, mm, 0, 0);
  return dt.toISOString();
}

export default function Admin() {
  const [slots, setSlots] = useState(['']); // tableau de datetimes
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);

  const handleDatetimeChange = (index, value) => {
    const newSlots = [...slots];
    newSlots[index] = value;
    setSlots(newSlots);
  };

  const addSlot = () => {
    setSlots([...slots, '']);
  };

  const removeSlot = (index) => {
    if (slots.length === 1) return; // Toujours au moins un input
    const newSlots = slots.filter((_, i) => i !== index);
    setSlots(newSlots);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage('');

    // Validation : aucun champ vide, format ISO valide
    for (let i = 0; i < slots.length; i++) {
      if (!slots[i]) {
        setMessage(`❌ Le créneau #${i + 1} est vide.`);
        return;
      }
      if (!localDatetimeToISOString(slots[i])) {
        setMessage(`❌ Le créneau #${i + 1} a un format invalide.`);
        return;
      }
    }

    setLoading(true);
    try {
      // Envoie chaque créneau en POST (sérialisé)
      for (const slot of slots) {
        const isoDatetime = localDatetimeToISOString(slot);
        await axios.post(
          'http://localhost:8000/api/slots',
          {
            datetime: isoDatetime,
            reserved: false,
          },
          {
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
            },
          }
        );
      }
      setMessage(`✅ ${slots.length} créneau${slots.length > 1 ? 'x' : ''} ajouté${slots.length > 1 ? 's' : ''} avec succès.`);
      setSlots(['']); // reset formulaire
    } catch (err) {
      const serverMsg = err.response?.data?.detail || err.response?.data?.message || err.message;
      setMessage(`❌ Erreur : ${serverMsg}`);
      console.error(err.response || err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-tr from-blue-100 via-white to-blue-100 p-6">
      <div className="bg-white shadow-xl rounded-xl p-8 w-full max-w-lg">
        <h2 className="text-2xl font-bold mb-6 text-center text-blue-800">Ajouter des créneaux</h2>

        <form onSubmit={handleSubmit} className="space-y-6">
          {slots.map((slot, index) => (
            <div key={index} className="flex items-center space-x-3">
              <input
                type="datetime-local"
                value={slot}
                onChange={(e) => handleDatetimeChange(index, e.target.value)}
                className="flex-grow rounded-md border border-gray-300 shadow-sm px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                required
              />
              <button
                type="button"
                onClick={() => removeSlot(index)}
                disabled={slots.length === 1}
                className="text-red-500 hover:text-red-700 disabled:text-gray-300 transition"
                aria-label={`Supprimer créneau #${index + 1}`}
              >
                &times;
              </button>
            </div>
          ))}

          <button
            type="button"
            onClick={addSlot}
            className="w-full py-2 rounded-md font-semibold bg-green-600 text-white hover:bg-green-700 transition"
          >
            + Ajouter un créneau
          </button>

          <button
            type="submit"
            disabled={loading}
            className={`w-full py-3 rounded-md font-semibold text-white ${
              loading ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'
            } transition`}
          >
            {loading ? 'Ajout en cours...' : 'Ajouter tous les créneaux'}
          </button>
        </form>

        {message && (
          <div
            className={`mt-6 p-4 rounded ${
              message.startsWith('✅') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
            } text-center font-medium select-none`}
          >
            {message}
          </div>
        )}
      </div>
    </div>
  );
}
