// Fichier de composant pour le terrain de jeu
import Card from './Card.js';
import api from '../services/api.js';

export default {
    components: { Card },
    props: ['gameId'],
    template: `
    <div class="game-board-view" v-if="state">
        <div class="battle-bg"></div>

        <div class="top-hud">
            <div class="opponent-hand">
                <div class="card-back-item" v-for="n in state.p2.hand_count" :key="n"></div>
            </div>

            <div class="hero-stats-container">
                <div class="hero-avatar" style="background-image: url('./assets/img/avatars/ai_avatar.png');">
                    <div class="hero-hp-badge">{{ state.p2.hp }}</div>
                </div>
                <div class="mana-display">
                    💎 {{ state.p2.mana }} / {{ state.p2.max_mana }}
                </div>
            </div>
        </div>

        <div class="board-container">
            <div class="row-units opponent">
                <div class="card-slot" v-for="(unit, index) in state.p2.board" :key="'op-board-'+index">
                    <Card v-if="unit" :card="unit" :small="true" />
                </div>
                <div class="card-slot empty" v-for="n in (5 - state.p2.board.length)" :key="'op-empty-'+n"></div>
            </div>

            <div class="row-units player">
                <div class="card-slot" v-for="(unit, index) in state.p1.board" :key="'pl-board-'+index">
                    <Card v-if="unit" :card="unit" :small="true" />
                </div>
                <div class="card-slot empty" v-for="n in (5 - state.p1.board.length)" :key="'pl-empty-'+n"></div>
            </div>
        </div>

        <div class="bottom-hud">
            <div class="hero-stats-container">
                <div class="hero-avatar" :style="{ backgroundImage: 'url(' + userAvatar + ')' }">
                    <div class="hero-hp-badge">{{ state.p1.hp }}</div>
                </div>
                <div class="mana-display">
                    💎 {{ state.p1.mana }} / {{ state.p1.max_mana }}
                </div>
            </div>

            <div class="player-hand">
                <div class="hand-card-wrapper" 
                     v-for="(card, index) in state.p1.hand" 
                     :key="card.uid" 
                     :style="{ '--i': index, '--total': state.p1.hand.length }"
                     @click="playCard(card)">
                    <Card :card="card" />
                </div>
            </div>

            <button class="btn-end-turn" @click="endTurn">Fin du Tour</button>
        </div>
    </div>
    
    <div v-else class="loading-screen" style="color:white; text-align:center; padding-top:200px;">
        <h2>Chargement du terrain...</h2>
    </div>
    `,
    data() {
        return {
            state: null,
            timer: null
        }
    },
    computed: {
        userAvatar() {
            const user = JSON.parse(localStorage.getItem('user'));
            return user && user.avatar ? './assets/img/avatars/' + user.avatar : './assets/img/avatars/default.png';
        }
    },
    mounted() {
        this.fetchState();
        this.timer = setInterval(this.fetchState, 2000);
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },
    methods: {
        async fetchState() {
            try {
                const res = await axios.get(`http://localhost/anime_game_card/api/routes.php?action=get_game_state&id=${this.gameId}`, { withCredentials: true });
                this.state = res.data.state;
            } catch (e) {
                console.error("Erreur sync jeu", e);
            }
        },
        endTurn() {
            console.log("Fin du tour demandée");
        },
        playCard(card) {
            console.log("Carte cliquée:", card.name);
        }
    }
}