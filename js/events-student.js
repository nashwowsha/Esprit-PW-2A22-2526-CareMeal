/* ============================================
   CAREMEAL — STUDENT EVENTS
   js/events-student.js
   ============================================ */

const EventsStudent = {
  filter: 'all', // 'all' or 'my'
  
  events: [
    {
      id: 1,
      title: 'Journée Anti-Gaspi',
      type: 'Présentiel',
      date: '2026-05-10',
      start: '09:00',
      end: '12:00',
      location: 'Campus El Manar',
      capacity: 100,
      registered: 45,
      status: 'validated',
      desc: 'Distribution géante et ateliers anti-gaspillage sur le campus.',
      userSubscribed: false
    },
    {
      id: 2,
      title: 'Atelier Cuisine Zéro Déchet',
      type: 'Présentiel',
      date: '2026-04-08',
      start: '10:00',
      end: '13:00',
      location: 'Campus La Marsa',
      capacity: 30,
      registered: 30,
      status: 'validated',
      desc: 'Apprenez à cuisiner les restes de la semaine. Places limitées!',
      userSubscribed: false
    },
    {
      id: 3,
      title: 'Conférence Sensibilisation',
      type: 'En ligne',
      date: '2026-05-20',
      start: '18:00',
      end: '20:00',
      location: 'Zoom',
      capacity: 200,
      registered: 12,
      status: 'validated',
      desc: 'Comprendre l\'impact écologique du gaspillage avec nos experts.',
      userSubscribed: true
    },
    {
      id: 4,
      title: 'Événement Fantôme',
      type: 'Présentiel',
      date: '2026-06-01',
      start: '14:00',
      end: '16:00',
      location: 'Local B',
      capacity: 50,
      registered: 5,
      status: 'pending', // Must be hidden according to Rule 1
      desc: 'Ceci ne devrait pas être affiché.',
      userSubscribed: false
    }
  ],

  init() {
    this.render();
  },

  setFilter(f) {
    this.filter = f;
    document.getElementById('filter-all').className = f === 'all' ? 'btn btn-primary' : 'btn btn-outline';
    document.getElementById('filter-my').className = f === 'my' ? 'btn btn-primary' : 'btn btn-outline';
    this.render();
  },

  formatDate(d) {
    if(!d) return ''; 
    const [y,m,day] = d.split('-'); 
    return `${day}/${m}/${y}`; 
  },

  subscribeEvent(id) {
    const e = this.events.find(x => x.id === id);
    if(!e || e.registered >= e.capacity || e.userSubscribed) return;
    e.registered++;
    e.userSubscribed = true;
    this.render();
  },

  unsubscribeEvent(id) {
    const e = this.events.find(x => x.id === id);
    if(!e || !e.userSubscribed) return;
    e.registered--;
    e.userSubscribed = false;
    this.render();
  },

  render() {
    // Rule 1: Only validated events
    const validatedEvents = this.events.filter(e => e.status === 'validated');
    
    // Stats calculation
    const totalAvail = validatedEvents.filter(e => e.registered < e.capacity).length;
    const totalSubs = validatedEvents.filter(e => e.userSubscribed).length;
    const totalFull = validatedEvents.filter(e => e.registered >= e.capacity).length;
    
    document.getElementById('stat-available').innerText = totalAvail;
    document.getElementById('stat-subscribed').innerText = totalSubs;
    document.getElementById('stat-full').innerText = totalFull;

    // Filtering logic
    let displayList = validatedEvents;
    if(this.filter === 'my') {
      displayList = validatedEvents.filter(e => e.userSubscribed);
    }

    const container = document.getElementById('events-container');
    
    if (displayList.length === 0) {
      container.innerHTML = `<div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--color-text-muted); background: var(--color-panel-bg); border-radius: 8px;">Aucun événement trouvé.</div>`;
      return;
    }

    container.innerHTML = displayList.map(e => {
      // Icon & Type
      const typeIcon = e.type === 'Présentiel' ? '<i class="fa-solid fa-school"></i>' : '<i class="fa-solid fa-laptop"></i>';
      
      // Progress Bar logic
      const perc = (e.registered / e.capacity) * 100;
      const fill = Math.round(perc / 10) || 0;
      const bar = '█'.repeat(fill) + '░'.repeat(Math.max(0, 10 - fill));
      const isFull = e.registered >= e.capacity;
      
      // Button logic
      let btnHtml = '';
      if(e.userSubscribed) {
        btnHtml = `<button class="btn btn-outline" style="width:100%; border-color:var(--color-danger); color:var(--color-danger);" onclick="EventsStudent.unsubscribeEvent(${e.id})"><i class="fa-solid fa-xmark"></i> Se désinscrire</button>`;
      } else if(isFull) {
        btnHtml = `<button class="btn btn-secondary" style="width:100%; opacity:0.6; cursor:not-allowed;" disabled><i class="fa-solid fa-lock"></i> Complet</button>`;
      } else {
        btnHtml = `<button class="btn btn-primary" style="width:100%;" onclick="EventsStudent.subscribeEvent(${e.id})"><i class="fa-solid fa-plus"></i> S'inscrire</button>`;
      }

      return `
        <div class="card" style="padding:20px; display:flex; flex-direction:column;">
          <div style="flex:1;">
            <h3 style="margin-bottom:12px; font-size:1.2rem; color:var(--color-white);">${e.title}</h3>
            
            <div style="color:var(--color-text-muted); font-size:0.9rem; margin-bottom:16px;">
              <div style="margin-bottom:6px;">${typeIcon} ${e.type}</div>
              <div style="margin-bottom:6px;"><i class="fa-solid fa-calendar"></i> ${this.formatDate(e.date)} | ${e.start} → ${e.end}</div>
              <div><i class="fa-solid fa-location-dot"></i> ${e.location}</div>
            </div>

            <div style="font-family:monospace; color:var(--color-primary); font-size:0.9rem; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
               <span style="letter-spacing:1px; font-size:0.8rem; color:${isFull ? 'var(--color-danger)' : 'var(--color-success)'};">${bar}</span>
            </div>
            <div style="font-size:0.85rem; color:var(--color-text-muted); margin-bottom:16px;">
              ${e.registered}/${e.capacity} inscrits ${isFull ? '<span style="color:var(--color-danger);">(Complet)</span>' : ''}
            </div>
            
            <p style="font-size:0.9rem; color:var(--color-text); margin-bottom:20px; line-height:1.4;">${e.desc}</p>
          </div>
          
          <div style="margin-top:auto;">
            ${btnHtml}
          </div>
        </div>
      `;
    }).join('');
  }
};

document.addEventListener('DOMContentLoaded', () => {
  EventsStudent.init();
});