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

  useEffect(() => {
    fetchSlots();
  }, []);

  useEffect(() => {
    if (slots.length > 0) {
      const dates = [...new Set(slots.map(slot => 
        new Date(slot.datetime).toISOString().split('T')[0]
      ))].sort();
      setAvailableDates(dates);
    }
  }, [slots]);

  const fetchSlots = async () => {
    try {
      const res = await axios.get('http://localhost:8000/api/slots?reserved=false');
      setSlots(res.data['hydra:member'] || res.data);
    } catch (err) {
      console.error("Erreur lors du chargement des créneaux:", err);
      setMessage("❌ Erreur lors du chargement des créneaux");
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!selectedSlot) {
      setMessage('❌ Veuillez choisir un créneau');
      return;
    }

    setLoading(true);
    try {
      // Création du rendez-vous
      await axios.post('http://localhost:8000/api/appointments', {
        name: name.trim(),
        email: email.trim(),
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

      setMessage('✅ Rendez-vous réservé avec succès ! Un email de confirmation va être envoyé.');
      setName('');
      setEmail('');
      setSelectedSlot(null);
      setSelectedDate('');
      
      // Rafraîchir la liste des créneaux
      await fetchSlots();
    } catch (err) {
      console.error('Erreur:', err);
      setMessage(`❌ Erreur: ${err.response?.data?.message || err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const getDaysInMonth = (date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    const days = [];
    const currentDate = new Date(startDate);
    
    while (currentDate <= lastDay || days.length < 42) {
      days.push(new Date(currentDate));
      currentDate.setDate(currentDate.getDate() + 1);
    }
    
    return days;
  };

  const formatDate = (date) => {
    return date.toISOString().split('T')[0];
  };

  const isDateAvailable = (date) => {
    return availableDates.includes(formatDate(date));
  };

  const getSlotsForDate = (date) => {
    const dateStr = formatDate(date);
    return slots.filter(slot => 
      new Date(slot.datetime).toISOString().split('T')[0] === dateStr
    );
  };

  const monthNames = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
  ];

  const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

  const navigateMonth = (direction) => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + direction, 1));
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-green-50 via-white to-emerald-50">
      {/* Header */}
      <header className="bg-white/80 backdrop-blur-md shadow-lg border-b border-green-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <Link to="/" className="flex items-center space-x-2 text-green-600 hover:text-green-700">
              <span className="text-xl">←</span>
              <span className="font-medium">Retour à l'accueil</span>
            </Link>
            <h1 className="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
              📅 Réservation
            </h1>
            <div></div>
          </div>
        </div>
      </header>

      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid lg:grid-cols-2 gap-8">
          {/* Calendrier */}
          <div className="bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-2xl font-bold text-gray-900">
                {monthNames[currentDate.getMonth()]} {currentDate.getFullYear()}
              </h2>
              <div className="flex space-x-2">
                <button
                  onClick={() => navigateMonth(-1)}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  ←
                </button>
                <button
                  onClick={() => navigateMonth(1)}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  →
                </button>
              </div>
            </div>

            {/* En-têtes des jours */}
            <div className="grid grid-cols-7 gap-1 mb-2">
              {dayNames.map(day => (
                <div key={day} className="p-2 text-center text-sm font-medium text-gray-600">
                  {day}
                </div>
              ))}
            </div>

            {/* Grille du calendrier */}
            <div className="grid grid-cols-7 gap-1">
              {getDaysInMonth(currentDate).map((date, index) => {
                const isCurrentMonth = date.getMonth() === currentDate.getMonth();
                const isSelected = selectedDate === formatDate(date);
                const isAvailable = isDateAvailable(date);
                const isPast = date < new Date().setHours(0,0,0,0);
                
                return (
                  <button
                    key={index}
                    onClick={() => {
                      if (isCurrentMonth && isAvailable && !isPast) {
                        setSelectedDate(formatDate(date));
                        setSelectedSlot(null);
                      }
                    }}
                    disabled={!isCurrentMonth || !isAvailable || isPast}
                    className={`
                      p-3 text-sm rounded-lg transition-all duration-200 relative
                      ${!isCurrentMonth ? 'text-gray-300' : ''}
                      ${isPast ? 'text-gray-400 cursor-not-allowed' : ''}
                      ${isSelected ? 'bg-green-500 text-white ring-2 ring-green-300' : ''}
                      ${isCurrentMonth && isAvailable && !isPast && !isSelected ? 
                        'text-gray-900 hover:bg-green-100 cursor-pointer' : ''}
                      ${!isAvailable && isCurrentMonth && !isPast ? 'text-gray-400' : ''}
                    `}
                  >
                    {date.getDate()}
                    {isAvailable && isCurrentMonth && !isPast && (
                      <div className="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-green-500 rounded-full"></div>
                    )}
                  </button>
                );
              })}
            </div>

            <div className="mt-4 text-sm text-gray-600">
              <div className="flex items-center space-x-4">
                <div className="flex items-center space-x-1">
                  <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span>Créneaux disponibles</span>
                </div>
              </div>
            </div>
          </div>

          {/* Formulaire de réservation */}
          <div className="bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
            <h2 className="text-2xl font-bold text-gray-900 mb-6">Réserver un créneau</h2>
            
            {/* Créneaux disponibles pour la date sélectionnée */}
            {selectedDate && (
              <div className="mb-6">
                <h3 className="text-lg font-semibold text-gray-800 mb-3">
                  Créneaux du {new Date(selectedDate + 'T00:00:00').toLocaleDateString('fr-FR')}
                </h3>
                <div className="grid grid-cols-2 gap-2">
                  {getSlotsForDate(new Date(selectedDate + 'T00:00:00')).map(slot => (
                    <button
                      key={slot.id}
                      onClick={() => setSelectedSlot(slot)}
                      className={`
                        p-3 rounded-lg text-sm font-medium transition-all duration-200
                        ${selectedSlot?.id === slot.id 
                          ? 'bg-green-500 text-white ring-2 ring-green-300' 
                          : 'bg-gray-50 text-gray-700 hover:bg-green-50 hover:text-green-700'
                        }
                      `}
                    >
                      {new Date(slot.datetime).toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                      })}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {!selectedDate && (
              <div className="text-center py-8 text-gray-500">
                <div className="text-4xl mb-2">📅</div>
                <p>Sélectionnez d'abord une date dans le calendrier</p>
              </div>
            )}

            {selectedDate && getSlotsForDate(new Date(selectedDate + 'T00:00:00')).length === 0 && (
              <div className="text-center py-8 text-gray-500">
                <div className="text-4xl mb-2">😔</div>
                <p>Aucun créneau disponible pour cette date</p>
              </div>
            )}

            {selectedSlot && (
              <form onSubmit={handleSubmit} className="space-y-6">
                <div>
                  <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                    Nom complet
                  </label>
                  <input
                    id="name"
                    type="text"
                    placeholder="Votre nom"
                    value={name}
                    onChange={e => setName(e.target.value)}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                    required
                  />
                </div>

                <div>
                  <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                    Email
                  </label>
                  <input
                    id="email"
                    type="email"
                    placeholder="votre@email.com"
                    value={email}
                    onChange={e => setEmail(e.target.value)}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                    required
                  />
                </div>

                {/* Résumé de la réservation */}
                <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                  <h4 className="font-semibold text-green-800 mb-2">Résumé de votre réservation</h4>
                  <p className="text-green-700 text-sm">
                    📅 {new Date(selectedSlot.datetime).toLocaleDateString('fr-FR', {
                      weekday: 'long',
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric'
                    })}
                  </p>
                  <p className="text-green-700 text-sm">
                    🕐 {new Date(selectedSlot.datetime).toLocaleTimeString('fr-FR', {
                      hour: '2-digit',
                      minute: '2-digit'
                    })}
                  </p>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className={`
                    w-full py-4 px-6 rounded-lg font-semibold text-lg transition-all duration-200
                    ${loading 
                      ? 'bg-gray-400 cursor-not-allowed' 
                      : 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white transform hover:scale-105'
                    }
                  `}
                >
                  {loading ? (
                    <div className="flex items-center justify-center space-x-2">
                      <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                      <span>Réservation en cours...</span>
                    </div>
                  ) : (
                    'Confirmer la réservation'
                  )}
                </button>
              </form>
            )}

            {message && (
              <div className={`
                mt-6 p-4 rounded-lg text-center font-medium
                ${message.includes('❌') 
                  ? 'bg-red-50 text-red-700 border border-red-200' 
                  : 'bg-green-50 text-green-700 border border-green-200'
                }
              `}>
                {message}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}