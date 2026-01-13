import HUD from '/anime_game_card/js/components/HUD.js';

export default {
    components: { HUD },
    template: `
    <div class="battle-view">
        <HUD :user="user" @navigate="$emit('change-view', $event)" @logout="logout" />
        
        <button class="btn-back-hub" @click="goBack" v-if="matchState === 'idle'">⬅ Retour au QG</button>

        <div class="mode-selector" v-if="matchState === 'idle'">
            <div class="mode-card" :class="{ active: selectedMode === 'training' }" @click="selectedMode = 'training'">
                <h3>Entraînement</h3>
                <span class="mode-desc">vs IA (Facile)</span>
            </div>
            <div class="mode-card" :class="{ active: selectedMode === 'casual' }" @click="selectedMode = 'casual'">
                <h3>Partie Rapide</h3>
                <span class="mode-desc">PvP sans enjeu</span>
            </div>
            <div class="mode-card" :class="{ active: selectedMode === 'ranked' }" @click="selectedMode = 'ranked'">
                <h3>Classé</h3>
                <span class="mode-desc">Pour la gloire (ELO)</span>
            </div>
        </div>

        <h2 v-if="decks.length > 0 && matchState === 'idle'" style="margin-top: 10px; color: var(--accent);">
            {{ decks[selectedIndex].name }}
        </h2>

        <div class="carousel-container" v-if="matchState === 'idle' && decks.length > 0">
            <div class="deck-3d-wrapper">
                <div 
                    v-for="(deck, index) in decks" 
                    :key="deck.id"
                    class="deck-box-3d"
                    :class="{ active: index === selectedIndex, incomplete: deck.card_count < 50 }"
                    :style="getDeckStyle(index)"
                    @click="selectedIndex = index"
                >
                    <img :src="getHeroImage(deck.hero_card_id)" class="deck-cover" alt="Hero">
                    
                    <div class="deck-meta">
                        <div class="deck-title">{{ deck.name }}</div>
                        <div class="deck-stats">
                            <span>🃏 {{ deck.card_count }}/50</span>
                        </div>
                    </div>

                    <div class="lock-overlay" v-if="deck.card_count < 20">🔒</div>
                </div>
            </div>

            <div class="carousel-nav">
                <button class="nav-arrow" @click="prevDeck">❮</button>
                <button class="nav-arrow" @click="nextDeck">❯</button>
            </div>
        </div>

        <div v-if="decks.length === 0" class="empty-msg" style="margin-top: 100px;">
            <h2>Aucun Grimoire trouvé !</h2>
            <button class="btn-action" @click="$emit('change-view', 'CollectionView')">Créer un deck</button>
        </div>

        <div class="play-section">
            <button 
                v-if="matchState === 'idle' && decks.length > 0"
                class="btn-play-big"
                :disabled="currentDeckIncomplete"
                @click="startMatchmaking"
            >
                {{ selectedMode === 'training' ? 'COMMENCER' : 'TROUVER ADVERSAIRE' }}
            </button>

            <div v-if="matchState === 'searching'" class="searching-state">
                <div class="radar-anim"></div>
                <h2>Recherche en cours...</h2>
                <div class="timer-text">{{ formatTimer }}</div>
                <button class="btn-cancel-search" @click="cancelMatchmaking">Annuler</button>
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            user: JSON.parse(localStorage.getItem('user')) || {},
            decks: [],
            selectedIndex: 0,
            selectedMode: 'training', // training, casual ou ranked
            matchState: 'idle',
            searchTimer: 0,
            timerInterval: null
        }
    },
    computed: {
        currentDeckIncomplete() {
            if (this.decks.length === 0) return true;
            // Deck incomplet si moins de 50 cartes
            return this.decks[this.selectedIndex].card_count < 50;
        },
        formatTimer() {
            const min = Math.floor(this.searchTimer / 60);
            const sec = this.searchTimer % 60;
            return `${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
        }
    },
    mounted() {
        this.loadDecks();
    },
    methods: {
        logout() {
            if (confirm("Voulez-vous vraiment quitter l'Académie ?")) {
                axios.get('http://localhost/anime_game_card/api/routes.php?action=logout', { withCredentials: true })
                    .then(() => {
                        localStorage.removeItem('user');
                        this.$emit('change-view', 'LoginView');
                    });
            }
        },

        async loadDecks() {
            try {
                const response = await axios.get('http://localhost/anime_game_card/api/routes.php?action=get_collection', { withCredentials: true });
                if (response.data.decks) {
                    this.decks = response.data.decks;
                }
            } catch (e) {
                console.error("Erreur chargement decks", e);
            }
        },

        //Logique du carousel 3D
        getDeckStyle(index) {
            const offset = index - this.selectedIndex;
            const absOffset = Math.abs(offset);

            // Si trop loin, on cache
            if (absOffset > 2) return { opacity: 0, pointerEvents: 'none' };

            let transform = '';
            let zIndex = 10 - absOffset;
            let opacity = 1;

            if (offset === 0) {
                // Carte active (au centre)
                transform = `translateX(0) translateZ(200px) rotateY(0deg)`;
            } else if (offset < 0) {
                // Cartes à gauche
                transform = `translateX(${offset * 220}px) translateZ(${-absOffset * 100}px) rotateY(45deg)`;
                opacity = 0.6;
            } else {
                // Cartes à droite
                transform = `translateX(${offset * 220}px) translateZ(${-absOffset * 100}px) rotateY(-45deg)`;
                opacity = 0.6;
            }

            return {
                transform,
                zIndex,
                opacity
            };
        },
        nextDeck() {
            if (this.selectedIndex < this.decks.length - 1) this.selectedIndex++;
        },
        prevDeck() {
            if (this.selectedIndex > 0) this.selectedIndex--;
        },
        getHeroImage(cardId) {
            return cardId ? `./assets/img/cards/${cardId}.jpg` : './assets/img/card_back.jpg';
        },

        // Logique du matchmaking
        async startMatchmaking() {
            const deckId = this.decks[this.selectedIndex].id;

            if (this.selectedMode === 'training') {
                try {
                    // Appel API pour créer la partie
                    const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=start_training', { deck_id: deckId }, { withCredentials: true });

                    if (res.data.game_id) {
                        // On bascule sur la vue de jeu avec l'ID de la partie
                        this.$emit('change-view', 'GameBoard', { gameId: res.data.game_id });
                    }
                } catch (e) { alert("Erreur lancement"); }
                return;
            }

            // PvP
            this.matchState = 'searching';
            this.searchTimer = 0;
            this.timerInterval = setInterval(() => this.searchTimer++, 1000);

            try {
                await axios.post('http://localhost/anime_game_card/api/routes.php?action=join_queue', {
                    deck_id: deckId,
                    mode: this.selectedMode
                }, { withCredentials: true });

                this.pollMatch();

            } catch (e) {
                alert("Erreur matchmaking");
                this.cancelMatchmaking();
            }
        },

        async pollMatch() {
            if (this.matchState !== 'searching') return;

            try {
                const res = await axios.get('http://localhost/anime_game_card/api/routes.php?action=check_queue_status', { withCredentials: true });

                if (res.data.status === 'found') {
                    // Match trouvé
                    this.matchState = 'found';
                    clearInterval(this.timerInterval);
                    // Petit délai pour l'UX
                    setTimeout(() => {
                        this.$emit('change-view', 'GameBoard', { gameId: res.data.game_id });
                    }, 500);
                } else {
                    // Toujours en recherche, on re-check dans 2s
                    setTimeout(() => this.pollMatch(), 2000);
                }
            } catch (e) {
                console.error("Polling error", e);
                setTimeout(() => this.pollMatch(), 3000);
            }
        },

        // annulation du matchmaking
        async cancelMatchmaking() {
            clearInterval(this.timerInterval);
            this.matchState = 'idle';
            await axios.post('http://localhost/anime_game_card/api/routes.php?action=leave_queue', {}, { withCredentials: true });
        },

        // retour au hub
        goBack() {
            this.$emit('change-view', 'HubView');
        }
    }
}