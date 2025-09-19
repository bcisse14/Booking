import React, { useState } from 'react';
import axios from 'axios';
import { API } from '../lib/api';

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
  const [appointments, setAppointments] = useState([]);
  const [showAppointments, setShowAppointments] = useState(false);
  const [loadingAppointments, setLoadingAppointments] = useState(false);
  
  // États pour le calendrier
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState('');
  const [selectedAppointments, setSelectedAppointments] = useState([]);

  /* ---------- Helpers pour le calendrier ---------- */
  const pad2 = (n) => String(n).padStart(2, '0');

  const formatYMD = (d) => {
    if (!(d instanceof Date) || isNaN(d.getTime())) return '';
    const year = d.getFullYear();
    const month = pad2(d.getMonth() + 1);
    const day = pad2(d.getDate());
    return `${year}-${month}-${day}`;
  };

  const safeParseISO = (s) => {
    if (!s) return new Date(NaN);
    const d = new Date(s);
    return (d instanceof Date && !isNaN(d.getTime())) ? d : new Date(NaN);
  };

  // Générer les cellules du calendrier
  const generateCalendarCells = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const firstDayOfWeek = (firstDay.getDay() + 6) % 7; // Lundi = 0
    const numDays = lastDay.getDate();

    const cells = Array(firstDayOfWeek).fill(null);
    for (let d = 1; d <= numDays; d++) {
      cells.push(new Date(year, month, d));
    }
    return cells;
  };

  // Obtenir les rendez-vous pour une date donnée
  const getAppointmentsForDate = (date) => {
    const ymd = formatYMD(date);
    return appointments.filter(apt => {
      if (!apt.slot || !apt.slot.datetime) return false;
      const aptDate = safeParseISO(apt.slot.datetime);
      return formatYMD(aptDate) === ymd;
    });
  };

  const navigateMonth = (direction) => {
    const newDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + direction, 1);
    setCurrentDate(newDate);
  };

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

  const fetchAppointments = async () => {
    setLoadingAppointments(true);
    try {
  const response = await axios.get(API('/api/appointments'), {
        headers: { 'Accept': 'application/json' }
      });
      const confirmedAppointments = response.data.filter(apt => !apt.cancelled);
      setAppointments(confirmedAppointments);
      setShowAppointments(true);
    } catch (err) {
      console.error('Erreur appointments:', err);
      setMessage(`❌ Erreur: ${err.message}`);
    } finally {
      setLoadingAppointments(false);
    }
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
          API('/api/slots'),
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
        
        {/* Bouton pour voir les RDV */}
        <div className="mb-6 text-center">
          <button
            type="button"
            onClick={fetchAppointments}
            disabled={loadingAppointments}
            className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-400"
          >
            {loadingAppointments ? 'Chargement...' : '👥 Voir les RDV confirmés'}
          </button>
        </div>

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
        
        {/* Calendrier des rendez-vous */}
        {showAppointments && (
          <div className="mt-8 pt-6 border-t border-gray-200">
            <h3 className="text-lg font-semibold text-blue-800 mb-6 text-center">
              📅 Calendrier des rendez-vous confirmés ({appointments.length})
            </h3>
            
            {appointments.length === 0 ? (
              <p className="text-gray-600 text-center py-8">Aucun rendez-vous confirmé</p>
            ) : (
              <div className="bg-white rounded-xl border border-gray-200 p-6">
                {/* Navigation du calendrier */}
                <div className="flex justify-between items-center mb-6">
                  <button 
                    onClick={() => navigateMonth(-1)} 
                    className="text-xl font-bold px-3 py-1 rounded hover:bg-gray-100"
                  >
                    ‹
                  </button>
                  <h2 className="text-lg font-semibold text-gray-800">
                    {currentDate.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}
                  </h2>
                  <button 
                    onClick={() => navigateMonth(1)} 
                    className="text-xl font-bold px-3 py-1 rounded hover:bg-gray-100"
                  >
                    ›
                  </button>
                </div>

                {/* En-têtes des jours */}
                <div className="grid grid-cols-7 gap-2 text-center mb-3 text-sm font-semibold text-gray-600">
                  {['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map((d, i) => (
                    <div key={i}>{d}</div>
                  ))}
                </div>

                {/* Grille du calendrier */}
                <div className="grid grid-cols-7 gap-3">
                  {generateCalendarCells().map((date, idx) => {
                    if (!date) return <div key={`empty-${idx}`} className="w-11 h-11" />;

                    const ymd = formatYMD(date);
                    const dayNum = date.getDate();
                    const dayAppointments = getAppointmentsForDate(date);
                    const hasAppointments = dayAppointments.length > 0;

                    let base = "relative flex items-center justify-center w-11 h-11 rounded-full font-semibold transition-all select-none";
                    if (hasAppointments) {
                      base += " bg-gradient-to-tr from-blue-400 to-blue-600 text-white shadow-md hover:scale-110 cursor-pointer";
                    } else {
                      base += " text-gray-400 bg-gray-50";
                    }

                    if (selectedDate === ymd) base += " ring-4 ring-green-200 scale-110 shadow-[0_0_20px_rgba(34,197,94,0.15)]";

                    return (
                      <button
                        key={`${ymd}-${idx}`}
                        className={base}
                        disabled={!hasAppointments}
                        onClick={() => {
                          if (hasAppointments) {
                            setSelectedDate(ymd);
                            setSelectedAppointments(dayAppointments);
                          }
                        }}
                        type="button"
                      >
                        {dayNum}
                        {hasAppointments && (
                          <span className="absolute -top-2 -right-2 text-xs bg-green-600 text-white px-1.5 rounded-full">
                            {dayAppointments.length}
                          </span>
                        )}
                      </button>
                    );
                  })}
                </div>

                {/* Détails des appointments du jour sélectionné */}
                {selectedDate && selectedAppointments.length > 0 && (
                  <div className="mt-6 pt-4 border-t border-gray-200">
                    <h4 className="font-semibold text-gray-800 mb-3">
                      📋 Rendez-vous du {new Date(selectedDate + 'T00:00:00').toLocaleDateString('fr-FR', {
                        weekday: 'long',
                        year: 'numeric', 
                        month: 'long',
                        day: 'numeric'
                      })} ({selectedAppointments.length})
                    </h4>
                    <div className="space-y-3">
                      {selectedAppointments.map((apt) => (
                        <div key={apt.id} className="bg-blue-50 rounded-lg p-4 border border-blue-200">
                          <div className="flex justify-between items-start">
                            <div className="flex-1">
                              <div className="font-semibold text-blue-800 mb-2">
                                RDV #{apt.id}
                              </div>
                              {apt.slot && (
                                <div className="text-gray-700 mb-1">
                                  � {new Date(apt.slot.datetime).toLocaleString('fr-FR', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                  })}
                                </div>
                              )}
                              {apt.name && (
                                <div className="text-gray-800 font-medium mb-1">
                                  👤 {apt.name}
                                </div>
                              )}
                              {apt.email && (
                                <div className="text-gray-600">
                                  ✉️ {apt.email}
                                </div>
                              )}
                            </div>
                            <div className="text-green-600 text-sm font-medium">
                              ✅ Confirmé
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
