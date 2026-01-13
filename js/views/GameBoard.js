//Fichier de la vue du jeu
import Card from '../components/Card.js';

export default {
    components: { Card },
    template: `
    <div class="game-board" v-if="state">
        <!-- TOP: OPPONENT -->
        <div class="opponent-area">
            <div class="hero-zone">
                <img :src="'./assets/img/avatars/' + state.p2.avatar" class="hero-avatar">
                <div class="hero-stats">
                    <span class="hp-badge">❤️ {{ state.p2.hp }}</span>
                    <span class="mana-badge">💧 {{ state.p2.mana }}/{{ state.p2.max_mana }}</span>
                </div>
            </div>
            
            <div class="hand-zone opponent-hand">
                <div v-for="n in state.p2.hand_count" :key="n" class="card-back-mini"></div>
            </div>

            <div class="deck-zone opponent-deck">
                <div class="deck-pile">
                    <span class="deck-count">{{ state.p2.deck_count || 0 }}</span>
                </div>
            </div>
        </div>

        <!-- MID: BATTLEFIELD -->
        <div class="battlefield">
            <div class="board-row opponent-row">
                <div v-for="unit in state.p2.board" :key="unit.uid" class="board-unit" @click="handleTarget(unit.uid, 'unit')">
                    <img :src="'./assets/img/cards/' + unit.image" class="unit-img">
                    <div class="unit-stats">
                        <span class="u-atk">⚔️ {{ unit.attack }}</span>
                        <span class="u-hp">❤️ {{ unit.hp }}</span>
                    </div>
                    <!-- TOOLTIP INFO -->
                    <div class="unit-info-overlay">
                        <div class="unit-info-name">{{ unit.name }}</div>
                        <div class="unit-info-desc">{{ unit.description || unit.passive || 'Aucun effet' }}</div>
                    </div>
                </div>
            </div>
            
            <div class="board-divider">
                <div class="turn-indicator" :class="{ 'my-turn': state.is_my_turn, 'opp-turn': !state.is_my_turn }">
                    {{ state.is_my_turn ? "C'EST VOTRE TOUR" : "TOUR ADVERSE..." }} (T{{ state.turn }})
                </div>
                <button class="btn-end-turn" @click="endTurn" :disabled="loading || !state.is_my_turn">
                    {{ state.is_my_turn ? "FIN DE TOUR" : "ATTENTE..." }}
                </button>
                <button class="btn-surrender" @click="surrender" :disabled="loading" style="margin-top: 5px; font-size: 0.8em; background: #444;">ABANDONNER</button>
            </div>

            <div class="board-row player-row">
                <div v-for="unit in state.p1.board" :key="unit.uid" 
                     class="board-unit" 
                     :class="{ 'can-attack': unit.can_attack, 'selected': selectedAttacker === unit.uid }"
                     @click="selectAttacker(unit)">
                    <img :src="'./assets/img/cards/' + unit.image" class="unit-img">
                    <div class="unit-stats">
                        <span class="u-atk">⚔️ {{ unit.attack }}</span>
                        <span class="u-hp">❤️ {{ unit.hp }}</span>
                    </div>
                    <!-- TOOLTIP INFO -->
                    <div class="unit-info-overlay">
                        <div class="unit-info-name">{{ unit.name }}</div>
                        <div class="unit-info-desc">{{ unit.description || unit.passive || 'Aucun effet' }}</div>
                    </div>
                    <div v-if="unit.can_attack" class="sleep-indicator">💤</div>
                </div>
            </div>
        </div>

        <!-- BOT: PLAYER -->
        <div class="player-area">
            <div class="deck-zone player-deck">
                <div class="deck-pile">
                    <span class="deck-count">{{ state.p1.deck ? state.p1.deck.length : 0 }}</span>
                </div>
            </div>

            <div class="hero-zone" @click="handleTarget(null, 'hero')" :class="{ 'valid-target': selectedAttacker }">
                <img :src="'./assets/img/avatars/' + user.avatar" class="hero-avatar">
                 <div class="hero-stats">
                    <span class="hp-badge">❤️ {{ state.p1.hp }}</span>
                    <span class="mana-badge">💧 {{ state.p1.mana }}/{{ state.p1.max_mana }}</span>
                </div>
            </div>

            <div class="hand-zone player-hand">
                <div v-for="card in state.p1.hand" :key="card.uid" class="card-in-hand" @click="playCard(card)">
                    <Card :card="card" />
                    <div v-if="card.cost > state.p1.mana" class="mana-overlay"></div>
                </div>
            </div>
        </div>

        <!-- RESULTS OVERLAY -->
        <div v-if="state.status === 'finished'" class="game-over-overlay">
            <h1>{{ state.i_won ? 'VICTOIRE !' : 'DÉFAITE...' }}</h1>
            <button @click="$emit('change-view', 'HubView')">Retour au QG</button>
        </div>
    </div>
    <div v-else class="loading-screen">
        Chargement de la partie...
    </div>
    `,
    props: ['gameId'],
    data() {
        return {
            user: JSON.parse(localStorage.getItem('user')) || { avatar: 'default.png' },
            state: null,
            loading: false,
            selectedAttacker: null,
            pollInterval: null,
            isActive: true
        }
    },
    methods: {
        //Méthode de récupération de l'état de la partie
        async fetchGameState(isPolling = false) {
            if (!this.isActive) return;
            try {
                if (!this.gameId) return;

                const response = await axios.get(`http://localhost/anime_game_card/api/routes.php?action=get_game_state&id=${this.gameId}`, { withCredentials: true });

                if (response.data.state) {
                    this.state = response.data.state;

                    // Si la partie est finie, on arrête de spammer le serveur
                    if (this.state.status === 'finished') {
                        this.stopPolling();
                    } else {
                        this.checkTurn();
                    }
                }
            } catch (e) {
                if (!this.isActive) return;

                console.error("Erreur chargement:", e);
                if (isPolling && e.response && e.response.status === 404) {
                    this.stopPolling();
                    return;
                }

                alert("Erreur technique : " + (e.response?.data?.message || e.message));
                this.$emit('change-view', 'BattleView');
            }
        },
        //Méthode de jeu d'une carte
        async playCard(card) {
            if (this.state.p1.mana < card.cost) return;
            this.loading = true;
            try {
                const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=play_card', {
                    game_id: this.gameId,
                    card_uid: card.uid
                }, { withCredentials: true });
                this.state = res.data.state;
            } catch (e) { alert(e.response?.data?.message || "Erreur"); }
            this.loading = false;
        },
        selectAttacker(unit) {
            if (unit.can_attack) {
                if (this.selectedAttacker === unit.uid) this.selectedAttacker = null;
                else this.selectedAttacker = unit.uid;
            }
        },
        //Méthode de ciblage d'un unité
        async handleTarget(uid, type) {
            if (!this.selectedAttacker) return;

            this.loading = true;
            try {
                const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=attack', {
                    game_id: this.gameId,
                    attacker_uid: this.selectedAttacker,
                    target_uid: uid,
                    target_type: type
                }, { withCredentials: true });
                this.state = res.data.state;
                this.selectedAttacker = null;
            } catch (e) { alert(e.response?.data?.message || "Erreur"); }
            this.loading = false;
        },
        //Méthode de fin de tour
        async endTurn() {
            this.loading = true;
            try {
                const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=end_turn', {
                    game_id: this.gameId
                }, { withCredentials: true });
                this.state = res.data.state;
                this.checkTurn(); // Vérifier si je dois attendre
            } catch (e) { alert(e.response?.data?.message || "Erreur"); }
            this.loading = false;
        },
        //Méthode d'abandon
        async surrender() {
            if (!confirm("Voulez-vous vraiment abandonner ?")) return;
            this.loading = true;
            try {
                const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=surrender', {
                    game_id: this.gameId
                }, { withCredentials: true });
                this.state = res.data.state;
                this.stopPolling();
            } catch (e) { alert(e.response?.data?.message || "Erreur"); }
            this.loading = false;
        },
        //Méthode de vérification du tour
        checkTurn() {
            this.stopPolling();
            this.startPolling();
        },
        //Méthode de polling
        startPolling() {
            if (this.pollInterval) return;
            this.pollInterval = setInterval(async () => {
                await this.fetchGameState(true);
            }, 2000);
        },
        //Méthode d'arrêt du polling
        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        }
    },
    //Méthode de destruction
    beforeDestroy() {
        this.isActive = false;
        this.stopPolling();
    },
    mounted() {
        console.log("GameBoard mounted! ID:", this.gameId);
        if (!this.gameId) {
            alert("ID de partie manquant");
            this.$emit('change-view', 'BattleView');
            return;
        }
        this.fetchGameState();
        this.startPolling();
    }
}
