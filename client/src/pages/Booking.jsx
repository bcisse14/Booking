import React, { useEffect, useState } from 'react';
import axios from 'axios';

export default function Booking() {
  const [slots, setSlots] = useState([]);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [message, setMessage] = useState('');

  useEffect(() => {
    const fetchSlots = async () => {
      try {
        const res = await axios.get('http://localhost:8000/api/slots?reserved=false');
        setSlots(res.data['hydra:member'] || res.data);
      } catch (err) {
        console.error("Erreur lors du chargement des créneaux:", err);
      }
    };
    fetchSlots();
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!selectedSlot) {
      setMessage('Choisissez un créneau');
      return;
    }

    try {
      // Création du rendez-vous
      await axios.post('http://localhost:8000/api/appointments', {
        name,
        email,
        slot: `/api/slots/${selectedSlot.id}`,
        confirmed: false
      }, {
        headers: {
          'Content-Type': 'application/ld+json'
        }
      });

      // Mise à jour du slot comme réservé
      await axios.patch(`http://localhost:8000/api/slots/${selectedSlot.id}`, {
        reserved: true
      }, {
        headers: {
          'Content-Type': 'application/merge-patch+json'
        }
      });

      setMessage('Rendez-vous réservé !');
      setName('');
      setEmail('');
      setSelectedSlot(null);
      
      // Rafraîchir la liste des créneaux
      const res = await axios.get('http://localhost:8000/api/slots?reserved=false');
      setSlots(res.data['hydra:member'] || res.data);
    } catch (err) {
      setMessage(`Erreur: ${err.response?.data?.message || err.message}`);
    }
  };

  return (
    <div className="p-8 max-w-md mx-auto">
      <h2>Prendre rendez-vous</h2>
      <form onSubmit={handleSubmit} className="space-y-4">
        <select 
          onChange={e => {
            const slot = slots.find(s => s.id === parseInt(e.target.value));
            setSelectedSlot(slot);
          }} 
          value={selectedSlot?.id || ''} 
          className="border p-2 w-full" 
          required
        >
          <option value="">Sélectionnez un créneau</option>
          {slots.map(slot => (
            <option key={slot.id} value={slot.id}>
              {new Date(slot.datetime).toLocaleString()}
            </option>
          ))}
        </select>
        <input
          type="text"
          placeholder="Nom"
          value={name}
          onChange={e => setName(e.target.value)}
          className="border p-2 w-full"
          required
        />
        <input
          type="email"
          placeholder="Email"
          value={email}
          onChange={e => setEmail(e.target.value)}
          className="border p-2 w-full"
          required
        />
        <button type="submit" className="bg-green-500 text-white px-4 py-2">
          Réserver
        </button>
      </form>
      <p>{message}</p>
    </div>
  );
}