// src/pages/Booking.jsx
import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Link } from 'react-router-dom';

export default function Booking() {
  const [slots, setSlots] = useState([]);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [selectedDate, setSelectedDate] = useState('');
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [availableDates, setAvailableDates] = useState([]);

  /* ---------- Helpers pour dates (locale-safe) ---------- */
  const pad2 = (n) => String(n).padStart(2, '0');

  const formatYMD = (d) => {
    if (!(d instanceof Date) || isNaN(d.getTime())) return '';
    const year = d.getFullYear();
    const month = pad2(d.getMonth() + 1);
    const day = pad2(d.getDate());
    return `${year}-${month}-${day}`;
  };

  const isValidDate = (d) => d instanceof Date && !isNaN(d.getTime());

  /**
   * safeParseISO: robuste face aux ISO avec ou sans offset.
   */
  const safeParseISO = (s) => {
    if (!s) return new Date(NaN);
    if (/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?(?:Z|[+-]\d{2}:\d{2})$/.test(s) || /Z$|[+-]\d{2}:\d{2}$/.test(s)) {
      const d = new Date(s);
      return isValidDate(d) ? d : new Date(NaN);
    }
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
    if (m) {
      const year = Number(m[1]);
      const month = Number(m[2]) - 1;
      const day = Number(m[3]);
      const hour = Number(m[4] || 0);
      const min = Number(m[5] || 0);
      const sec = Number(m[6] || 0);
      return new Date(year, month, day, hour, min, sec);
    }
    const d = new Date(s);
    return isValidDate(d) ? d : new Date(NaN);
  };

  // Convertit 'YYYY-MM-DD' en Date locale (00:00 locale)
  const ymdToLocalDate = (ymd) => {
    if (!ymd || typeof ymd !== 'string') return new Date(NaN);
    const m = ymd.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return new Date(NaN);
    return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
  };

  /* ---------- util: détecte si un slot est réservé (différents formats possibles) ---------- */
  const isReservedFlag = (slot) => {
    if (!slot) return false;
    // priorise la propriété 'reserved', fallback à booléens communs
    const v = slot.reserved ?? slot.isReserved ?? slot.taken ?? slot.booked ?? null;
    if (v === null || v === undefined) return false;
    return v === true || v === 'true' || v === 1 || v === '1';
  };

  /* ---------- Fetch + normalisation ---------- */
  useEffect(() => {
    fetchSlots();
  }, []);

  useEffect(() => {
    if (!Array.isArray(slots) || slots.length === 0) {
      setAvailableDates([]);
      return;
    }
    const s = new Set();
    // ATTENTION: on utilise uniquement les slots non réservés pour availableDates
    slots.forEach(slot => {
      if (isReservedFlag(slot)) return; // ignore réservés
      const d = safeParseISO(slot.datetime);
      if (isValidDate(d)) s.add(formatYMD(d));
    });
    setAvailableDates([...s].sort());
  }, [slots]);

  const fetchSlots = async () => {
    try {
      // Si tu utilises Vite proxy -> '/api/slots?reserved=false'
      // Sinon -> 'http://localhost:8000/api/slots?reserved=false'
      const res = await axios.get('http://localhost:8000/api/slots?reserved=false');
      const data = res?.data;
      let items = [];

      if (Array.isArray(data)) {
        items = data;
      } else if (data && Array.isArray(data['hydra:member'])) {
        items = data['hydra:member'];
      } else if (data && Array.isArray(data.items)) {
        items = data.items;
      } else if (data && typeof data === 'object') {
        const arrProp = Object.keys(data).find(k => Array.isArray(data[k]));
        if (arrProp) items = data[arrProp];
        else items = [data];
      }

      // filtrage : supprime explicitement tout item marqué réservé
      items = items
        .filter(s => s && (s.id !== undefined) && (s.datetime))
        .map(s => ({ ...s }))
        .filter(s => !isReservedFlag(s))
        .sort((a, b) => safeParseISO(a.datetime).getTime() - safeParseISO(b.datetime).getTime());

      setSlots(items);
    } catch (err) {
      console.error('Erreur lors du chargement des créneaux:', err);
      setMessage('❌ Erreur lors du chargement des créneaux');
      setSlots([]);
    }
  };

  /* ---------- Utilitaires calendrier ---------- */
  const getSlotsForDate = (dateOrYmd) => {
    const ymd = typeof dateOrYmd === 'string'
      ? (/^\d{4}-\d{2}-\d{2}$/.test(dateOrYmd) ? dateOrYmd : formatYMD(safeParseISO(dateOrYmd)))
      : formatYMD(dateOrYmd);
    // on s'assure de n'utiliser que les slots non réservés
    const arr = Array.isArray(slots) ? slots.filter(s => !isReservedFlag(s)) : [];
    return arr
      .filter(slot => {
        const d = safeParseISO(slot.datetime);
        if (!isValidDate(d)) return false;
        return formatYMD(d) === ymd;
      })
      .sort((a, b) => safeParseISO(a.datetime).getTime() - safeParseISO(b.datetime).getTime());
  };

  const getDaysInMonthGrid = (date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDayIndex = new Date(year, month, 1).getDay(); // 0 = Dimanche
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells = [];
    for (let i = 0; i < firstDayIndex; i++) cells.push(null);
    for (let d = 1; d <= daysInMonth; d++) cells.push(new Date(year, month, d));
    while (cells.length % 7 !== 0) cells.push(null);
    return cells;
  };

  /* ---------- PATCH helper (essaye plusieurs Content-Types si besoin) ---------- */
  const patchSlot = async (slotId, bodyObj) => {
    const url = `http://localhost:8000/api/slots/${slotId}`;
    const contentTypes = ['application/json', 'application/ld+json'];
    let lastError = null;

    for (const ct of contentTypes) {
      try {
        const res = await axios.patch(url, bodyObj, {
          headers: {
            'Content-Type': ct,
            Accept: 'application/ld+json'
          }
        });
        return res;
      } catch (err) {
        lastError = err;
        const status = err?.response?.status;
        if (status && status !== 415) throw err;
      }
    }
    throw lastError;
  };

  /* ---------- Submit : création appointment puis patch slot ---------- */
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!selectedSlot) {
      setMessage('❌ Veuillez choisir un créneau');
      return;
    }
    if (!name.trim() || !email.trim()) {
      setMessage('❌ Nom et email requis');
      return;
    }
    setLoading(true);
    setMessage('');

    try {
      // 1) Création Appointment
      await axios.post('http://localhost:8000/api/appointments', {
        name: name.trim(),
        email: email.trim(),
        slot: `/api/slots/${selectedSlot.id}`,
        confirmed: false
      }, {
        headers: { 'Content-Type': 'application/ld+json' }
      });

      // 2) PATCH slot reserved
      await patchSlot(selectedSlot.id, { reserved: true });

      // Succès
      setMessage('✅ Rendez-vous réservé avec succès ! Un email de confirmation va être envoyé.');
      setName('');
      setEmail('');
      setSelectedSlot(null);
      setSelectedDate('');
      await fetchSlots();
    } catch (err) {
      console.error('Erreur:', err);

      let serverMsg = '';
      if (err?.response?.data) {
        try {
          serverMsg = JSON.stringify(err.response.data);
        } catch {
          serverMsg = String(err.response.data);
        }
      } else {
        serverMsg = err.message || String(err);
      }

      setMessage(`❌ Erreur: ${serverMsg}`);
      // Si erreur 409, on refetch pour synchroniser l'UI
      if (err?.response?.status === 409) {
        await fetchSlots();
      }
    } finally {
      setLoading(false);
    }
  };

  /* ---------- UI helpers ---------- */
  const monthNames = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
  ];
  const dayNames = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
  const navigateMonth = (direction) => {
    setCurrentDate(prev => new Date(prev.getFullYear(), prev.getMonth() + direction, 1));
    setSelectedDate('');
    setSelectedSlot(null);
    setMessage('');
  };

  const todayYMD = formatYMD(new Date());
  const cells = getDaysInMonthGrid(currentDate);

  /* ---------- Render ---------- */
  const selectedLocalDate = selectedDate ? ymdToLocalDate(selectedDate) : null;

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
      <header className="bg-white/80 backdrop-blur-md shadow-lg border-b border-blue-100">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
          <Link to="/" className="text-gray-700 font-medium hover:text-gray-900">← Accueil</Link>
          <h1 className="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
            Réservation
          </h1>
          <div style={{ width: 80 }} />
        </div>
      </header>

      <main className="max-w-4xl mx-auto p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
        {/* Calendrier */}
        <section className="bg-white rounded-2xl p-6 shadow-lg">
          <div className="flex items-center justify-between mb-4">
            <button onClick={() => navigateMonth(-1)} className="text-xl font-bold px-3 py-1 rounded hover:bg-gray-100">‹</button>
            <h2 className="text-lg font-semibold">
              {monthNames[currentDate.getMonth()]} {currentDate.getFullYear()}
            </h2>
            <button onClick={() => navigateMonth(1)} className="text-xl font-bold px-3 py-1 rounded hover:bg-gray-100">›</button>
          </div>

          <div className="grid grid-cols-7 gap-2 text-center mb-3 text-sm font-semibold text-gray-600">
            {dayNames.map((d, i) => <div key={i}>{d}</div>)}
          </div>

          <div className="grid grid-cols-7 gap-3">
            {cells.map((date, idx) => {
              if (!date) return <div key={`empty-${idx}`} className="w-11 h-11" />;

              const ymd = formatYMD(date);
              const dayNum = date.getDate();
              const isPast = ymd < todayYMD;
              const available = availableDates.includes(ymd);
              const slotCount = getSlotsForDate(date).length;

              let base = "relative flex items-center justify-center w-11 h-11 rounded-full font-semibold transition-all select-none";
              if (isPast) base += " text-gray-300 bg-gray-50";
              else if (available) base += " bg-gradient-to-tr from-green-400 to-green-600 text-white shadow-md hover:scale-110 cursor-pointer";
              else base += " text-gray-400 bg-white";

              if (selectedDate === ymd) base += " ring-4 ring-blue-200 scale-110 shadow-[0_0_20px_rgba(37,99,235,0.15)]";

              return (
                <button
                  key={`${ymd}-${idx}`}
                  className={base}
                  disabled={isPast || !available}
                  onClick={() => {
                    if (!isPast && available) {
                      setSelectedDate(ymd);
                      setSelectedSlot(null);
                      setMessage('');
                    }
                  }}
                  aria-label={`Jour ${dayNum}${available ? ', disponible' : ', indisponible'}`}
                  type="button"
                >
                  {dayNum}
                  {available && (
                    <span className="absolute -top-2 -right-2 text-xs bg-blue-600 text-white px-1.5 rounded-full">
                      {slotCount}
                    </span>
                  )}
                </button>
              );
            })}
          </div>
        </section>

        {/* Formulaire & créneaux */}
        <section className="bg-white rounded-2xl p-6 shadow-lg">
          <h3 className="text-xl font-semibold mb-4">Réserver un créneau</h3>

          {!selectedDate && (
            <p className="mb-4 text-gray-600 italic">Choisissez un jour dans le calendrier pour voir les créneaux disponibles.</p>
          )}

          {selectedDate && (
            <>
              <div className="mb-4 text-gray-700">
                <strong>Créneaux pour </strong>
                {isValidDate(selectedLocalDate)
                  ? selectedLocalDate.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
                  : selectedDate}
              </div>

              <div className="flex flex-wrap gap-3 mb-6">
                {getSlotsForDate(selectedDate).map(slot => {
                  const parsed = safeParseISO(slot.datetime);
                  const timeStr = isValidDate(parsed)
                    ? parsed.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
                    : slot.datetime;
                  const reserved = isReservedFlag(slot); // Should be false because we filtered, but double check
                  const isSel = selectedSlot?.id === slot.id;
                  return (
                    <button
                      key={slot.id}
                      onClick={() => !reserved && setSelectedSlot(slot)}
                      type="button"
                      disabled={reserved}
                      className={`px-4 py-2 rounded-full font-semibold transition-shadow
                        ${isSel ? 'bg-blue-600 text-white shadow-md' : reserved ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'}`}
                    >
                      {timeStr}{reserved ? ' (réservé)' : ''}
                    </button>
                  );
                })}
                {getSlotsForDate(selectedDate).length === 0 && (
                  <p className="text-gray-500 italic">Aucun créneau disponible ce jour.</p>
                )}
              </div>
            </>
          )}

          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <input
              type="text"
              placeholder="Votre nom complet"
              value={name}
              onChange={e => setName(e.target.value)}
              required
              className="rounded-lg px-4 py-2 border border-gray-200 bg-gray-50 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-200"
            />
            <input
              type="email"
              placeholder="Votre email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              className="rounded-lg px-4 py-2 border border-gray-200 bg-gray-50 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-200"
            />

            <button
              type="submit"
              disabled={loading}
              className={`py-3 rounded-lg font-semibold transition-colors
                ${loading ? 'bg-gray-300 text-gray-600 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'}`}
            >
              {loading ? 'Réservation...' : 'Réserver'}
            </button>
          </form>

          {message && (
            <div className={`mt-4 p-3 rounded ${message.startsWith('✅') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
              {message}
            </div>
          )}
        </section>
      </main>
    </div>
  );
}
