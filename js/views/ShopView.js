// Fichier de la vue de la boutique
import HUD from '/anime_game_card/js/components/HUD.js';
import Card from '../components/Card.js';

export default {
    components: { HUD, Card },
    template: `
    <div class="shop-view">
        <HUD :user="user" @navigate="$emit('change-view', $event)" @logout="logout" />
        
        <button class="btn-back-hub" @click="$emit('change-view', 'HubView')">⬅ Retour au QG</button>

        <div v-if="state === 'browsing'" class="shop-container">
            <h1 class="shop-title">BOUTIQUE ACADÉMIQUE</h1>
            
            <div class="packs-grid">
                <div class="booster-pack standard" @click="confirmBuy('standard', 'gold', 100)">
                    <div class="pack-art">🧬</div>
                    <h2>STANDARD</h2>
                    <p>5 Cartes Aléatoires</p>
                    <div class="price-tag">
                        <span>💰 100</span>
                    </div>
                </div>

                <div class="booster-pack premium" @click="confirmBuy('premium', 'gems', 200)">
                    <div class="pack-art">💎</div>
                    <h2>PREMIUM</h2>
                    <p>Garantie Rare ou +</p>
                    <div class="price-tag gem-price">
                        <span>💎 200</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="state === 'opening'" class="opening-stage">
            <h2 class="tension-text">Clique pour ouvrir !</h2>
            <div class="pack-3d" :class="{ shaking: isShaking }" @click="openPack">
                <div class="pack-face">{{ selectedPackType === 'premium' ? '💎' : '🧬' }}</div>
            </div>
        </div>

        <div v-else-if="state === 'revealed'" class="reveal-stage">
            <h2>RÉCOMPENSES</h2>
            <div class="cards-reveal-grid">
                <div v-for="(card, index) in pulledCards" :key="index" 
                     class="reveal-slot" 
                     :class="[{ flipped: card.flipped, revealing: card.revealing }, card.rarity.toLowerCase()]"
                     @click="flipCard(index)">
                    
                    <div class="card-inner">
                        <div class="card-back"></div>
                        <div class="card-front">
                            <Card :card="card" />
                            <div v-if="['Legendary', 'Mythic'].includes(card.rarity)" class="god-rays"></div>
                            <!-- Shine Effect for Rare -->
                            <div v-if="card.rarity === 'Rare'" class="shine-effect"></div>
                        </div>
                    </div>

                    <!-- Particles for Rare -->
                    <div v-if="card.rarity === 'Rare' && card.flipped" class="rare-particles">
                        <span v-for="n in 5" :key="n" :class="'spark s'+n"></span>
                    </div>

                    <!-- Shockwave for Epic -->
                    <div v-if="card.rarity === 'Epic' && card.flipped" class="shockwave"></div>

                    <!-- Legendary Extras -->
                    <div v-if="card.rarity === 'Legendary' && card.flipped" class="legendary-extras">
                        <div class="legendary-text">LEGENDARY</div>
                         <div class="confetti-container">
                            <span v-for="n in 20" :key="n" :class="'confetti c'+n"></span>
                        </div>
                    </div>

                    <!-- Mythic Extras -->
                     <div v-if="card.rarity === 'Mythic'" class="mythic-wrapper">
                         <!-- Lightning SVG -->
                         <svg class="mythic-lightning" viewBox="0 0 200 300" v-if="card.flipped">
                            <path d="M100,0 L20,100 L80,100 L10,300 L90,150 L30,150 L100,0; M120,0 L40,120 L100,120 L30,300 L110,170 L50,170 L120,0; M80,0 L0,80 L60,80 L0,280 L70,130 L10,130 L80,0" fill="none" stroke="cyan" stroke-width="2">
                                <animate attributeName="d" values="M100,0 L20,100 L80,100 L10,300 L90,150 L30,150 L100,0; M120,0 L40,120 L100,120 L30,300 L110,170 L50,170 L120,0; M80,0 L0,80 L60,80 L0,280 L70,130 L10,130 L80,0" dur="0.2s" repeatCount="indefinite" />
                            </path>
                         </svg>
                         <!-- Holographic Overlay -->
                         <div class="holographic-foil" v-if="card.flipped"></div>
                     </div>

                </div>
            </div>
            <button class="btn-action btn-collect" v-if="allFlipped" @click="resetShop">AJOUTER À LA COLLECTION</button>
        </div>
    </div>
    `,
    data() {
        return {
            user: JSON.parse(localStorage.getItem('user')) || {},
            state: 'browsing',
            selectedPackType: 'standard',
            pulledCards: [],
            isShaking: false,
            allFlipped: false
        }
    },
    methods: {
        logout() { this.$emit('logout'); },
        //Méthode de confirmation d'achat
        async confirmBuy(type, currency, cost) {
            if (this.user[currency] < cost) return alert("Fonds insuffisants !");
            if (confirm(`Acheter un booster ${type} pour ${cost} ${currency === 'gold' ? 'Or' : 'Gemmes'} ?`)) {
                try {
                    const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=buy_booster', { type, currency }, { withCredentials: true });
                    this.user[currency] = res.data.newBalance;
                    localStorage.setItem('user', JSON.stringify(this.user));
                    this.pulledCards = res.data.cards.map(c => ({ ...c, flipped: false, revealing: false }));
                    this.selectedPackType = type;
                    this.state = 'opening';
                    this.isShaking = true;
                } catch (e) { alert(e.response?.data?.message || "Erreur Achat"); }
            }
        },
        openPack() {
            this.isShaking = false;
            setTimeout(() => { this.state = 'revealed'; }, 500);
        },
        flipCard(index) {
            const card = this.pulledCards[index];
            if (card.flipped || card.revealing) return;

            const rarity = card.rarity;
            let delay = 0;

            if (rarity === 'Common') delay = 0;
            else if (rarity === 'Rare') { card.revealing = true; delay = 500; }
            else if (rarity === 'Epic') { card.revealing = true; delay = 800; }
            else if (rarity === 'Legendary') {
                card.revealing = true;
                document.body.classList.add('dim-overlay');
                delay = 1000;
            }
            else if (rarity === 'Mythic') {
                card.revealing = true;
                document.body.classList.add('time-stop-active');
                delay = 1500;
            }

            if (navigator.vibrate) navigator.vibrate(50); // Petit retour haptique

            setTimeout(() => {
                card.revealing = false;
                card.flipped = true;

                if (rarity === 'Legendary') document.body.classList.remove('dim-overlay');
                if (rarity === 'Mythic') {
                    setTimeout(() => document.body.classList.remove('time-stop-active'), 2000);
                    document.body.classList.add('shaking'); // Screen shake
                    setTimeout(() => document.body.classList.remove('shaking'), 500);
                }

                if (navigator.vibrate) {
                    if (rarity === 'Legendary') navigator.vibrate(500);
                    if (rarity === 'Mythic') navigator.vibrate([200, 100, 200, 100, 500]);
                }

                this.allFlipped = this.pulledCards.every(c => c.flipped);
            }, delay);
        },
        //Méthode de réinitialisation de la boutique
        resetShop() {
            this.state = 'browsing';
            this.pulledCards = [];
            this.allFlipped = false;
            document.body.classList.remove('time-stop-active');
            document.body.classList.remove('dim-overlay');
        },
        //Méthode de gestion du déplacement de la souris
        handleMouseMove(e) {
            const cards = document.querySelectorAll('.reveal-slot.mythic');
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--mx', `${x}px`);
                card.style.setProperty('--my', `${y}px`);
                card.style.setProperty('--rx', `${(y - rect.height / 2) / 10}deg`);
                card.style.setProperty('--ry', `${(x - rect.width / 2) / 10}deg`);
            });
        }
    },
    mounted() { window.addEventListener('mousemove', this.handleMouseMove); },
    beforeDestroy() {
        window.removeEventListener('mousemove', this.handleMouseMove);
        document.body.classList.remove('time-stop-active');
        document.body.classList.remove('dim-overlay');
    }
}