// Fichier de la vue du classement
import HUD from '/anime_game_card/js/components/HUD.js';
import Card from '../components/Card.js';

export default {
    components: { HUD, Card },
    template: `
    <div class="leaderboard-view">
        <HUD :user="user" @navigate="$emit('change-view', $event)" @logout="logout" />
        <button class="btn-back-hub" @click="$emit('change-view', 'HubView')">⬅ Retour au QG</button>

        <div class="split-container">
            
            <div class="panel ranking-panel glass-panel">
                <div class="panel-header">
                    <h2>🏆 CLASSEMENT</h2>
                    <div class="ranking-filters">
                        <button @click="loadRanking('global')" :class="{active: rankType === 'global'}">Global</button>
                        <button @click="loadRanking('friends')" :class="{active: rankType === 'friends'}">Amis</button>
                    </div>
                </div>

                <div class="ranking-list">
                    <div class="ranking-header-row">
                        <span class="rank-col">#</span>
                        <span class="player-col">Joueur</span>
                        <span class="elo-col">ELO</span>
                    </div>
                    <div v-for="p in ranking" :key="p.id" class="ranking-row" :class="{me: p.is_me, top3: p.rank <= 3}">
                        <span class="rank-col">
                            <span v-if="p.rank === 1">🥇</span>
                            <span v-else-if="p.rank === 2">🥈</span>
                            <span v-else-if="p.rank === 3">🥉</span>
                            <span v-else>{{ p.rank }}</span>
                        </span>
                        <div class="player-col">
                            <img :src="getAvatarUrl(p.avatar)" class="rank-avatar">
                            <div class="rank-info">
                                <span class="rank-name">{{ p.username }}</span>
                                <span class="rank-title">{{ p.title }}</span>
                            </div>
                        </div>
                        <span class="elo-col">{{ p.elo }}</span>
                    </div>
                </div>
            </div>

            <div class="panel quests-panel glass-panel">
                <div class="panel-header">
                    <h2>📜 MISSIONS</h2>
                    <div class="quest-tabs">
                        <button @click="questTab = 'daily'" :class="{active: questTab === 'daily'}">Jour</button>
                        <button @click="questTab = 'weekly'" :class="{active: questTab === 'weekly'}">Semaine</button>
                        <button @click="questTab = 'pass'" :class="{active: questTab === 'pass'}">Cursus</button>
                    </div>
                </div>

                <div class="quest-list">
                    <template v-if="questTab !== 'pass'">
                        <div v-for="q in displayedQuests" :key="q.id" class="quest-card" :class="{completed: q.is_completed, claimed: q.is_claimed}">
                            <div class="quest-info">
                                <h3>{{ q.title }}</h3>
                                <p>{{ q.description }}</p>
                                <div class="quest-progress-bar">
                                    <div class="fill" :style="{width: (q.current_progress / q.objective_count * 100) + '%'}"></div>
                                    <span>{{ q.current_progress }} / {{ q.objective_count }}</span>
                                </div>
                            </div>
                            <div class="quest-reward">
                                <div class="reward-icon">{{ getRewardIcon(q.reward_type) }}</div>
                                <span>{{ q.reward_amount }}</span>
                                <button v-if="q.is_completed && !q.is_claimed" @click="claim(q)" class="btn-claim">RÉCUPÉRER</button>
                                <span v-if="q.is_claimed" class="claimed-check">✔</span>
                            </div>
                        </div>
                        <p v-if="displayedQuests.length === 0" class="empty-msg">Aucune mission disponible.</p>
                    </template>

                    <div v-else class="pass-container">
                        <div class="pass-header">
                            <h3>Niveau Actuel : {{ quests.pass.current_level }}</h3>
                            <button v-if="!quests.pass.is_premium" @click="buyPass" class="btn-premium">S'inscrire au Cursus Élite (950💎)</button>
                            <span v-else class="premium-badge">✨ ÉLITE ACTIVÉ</span>
                        </div>
                        <div class="pass-track">
                            <div v-for="lvl in quests.pass.track" :key="lvl.level" class="pass-step" :class="{unlocked: lvl.status !== 'locked', claimed: lvl.status === 'claimed'}">
                                <div class="step-level">{{ lvl.level }}</div>
                                <div class="step-rewards">
                                    <div class="reward free">🎁 {{ lvl.free_reward }}</div>
                                    <div class="reward premium" v-if="quests.pass.is_premium">💎 {{ lvl.premium_reward }}</div>
                                    <div class="reward locked" v-if="!quests.pass.is_premium">🔒 (Premium)</div>
                                </div>
                                <button v-if="lvl.status === 'ready'" @click="claimPassLevel(lvl.level)" class="btn-claim-pass">
                                    RÉCUPÉRER
                                </button>
                                <span v-if="lvl.status === 'claimed'" class="claimed-check">✔</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- OVERLAY ANIMATION OUVERTURE -->
        <div v-if="animState !== 'hidden'" class="pack-overlay">
            <div class="overlay-content">
                <div v-if="animState === 'opening'" class="opening-stage">
                    <h2 class="tension-text">Récompense obtenue !</h2>
                    <div class="pack-3d" :class="{ shaking: isShaking }" @click="openPack">
                        <div class="pack-face">🎁</div>
                    </div>
                </div>

                <div v-else-if="animState === 'revealed'" class="reveal-stage">
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
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn-action btn-collect" v-if="allFlipped" @click="closeAnim">COLLECTER</button>
                </div>
            </div>
        </div>

    </div>
    `,
    data() {
        return {
            user: JSON.parse(localStorage.getItem('user')) || {},
            rankType: 'global',
            ranking: [],
            questTab: 'daily',
            quests: { daily: [], weekly: [], pass: { track: [], current_level: 1 } },
            animState: 'hidden', // hidden, opening ou revealed
            pulledCards: [],
            isShaking: false,
            allFlipped: false
        }
    },
    computed: {
        displayedQuests() {
            if (this.questTab === 'pass') return [];
            return this.quests[this.questTab] || [];
        }
    },
    mounted() {
        this.loadRanking('global');
        this.loadQuests();
    },
    methods: {
        logout() { },
        async loadRanking(type) {
            this.rankType = type;
            const res = await axios.get(`http://localhost/anime_game_card/api/routes.php?action=get_ranking&type=${type}`, { withCredentials: true });
            this.ranking = res.data.ranking;
        },
        //Méthode d'achat de pass
        async buyPass() {
            if (confirm("Acheter le Cursus Élite pour 950 Gemmes ?")) {
                try {
                    await axios.post('http://localhost/anime_game_card/api/routes.php?action=buy_pass', {}, { withCredentials: true });
                    alert("Félicitations !");
                    this.loadQuests();
                } catch (e) { alert(e.response?.data?.message || "Erreur"); }
            }
        },
        //Méthode de chargement des quêtes
        async loadQuests() {
            const res = await axios.get('http://localhost/anime_game_card/api/routes.php?action=get_quests', { withCredentials: true });
            this.quests = {
                daily: Object.values(res.data.daily),
                weekly: Object.values(res.data.weekly),
                pass: res.data.pass
            };
        },
        //Méthode de réclamation de quête
        async claim(quest) {
            try {
                const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=claim_quest', { quest_id: quest.id }, { withCredentials: true });
                alert(`Récompense : +${res.data.reward.amount} ${res.data.reward.type}`);
                this.loadQuests();
            } catch (e) { alert(e.response?.data?.message); }
        },
        //Méthode de réclamation de palier du pass
        async claimPassLevel(level) {
            try {
                const res = await axios.post('http://localhost/anime_game_card/api/routes.php?action=claim_pass_level', { level: level }, { withCredentials: true });

                if (res.data.cards && res.data.cards.length > 0) {
                    this.pulledCards = res.data.cards.map(c => ({ ...c, flipped: false, revealing: false }));
                    this.animState = 'opening';
                    this.isShaking = true;
                } else {
                    alert(res.data.message); // Cas où c'est juste de l'or ou autre (si implémenté un jour)
                }

                this.loadQuests();
            } catch (e) { alert(e.response?.data?.message || "Erreur"); }
        },
        openPack() {
            this.isShaking = false;
            setTimeout(() => { this.animState = 'revealed'; }, 500);
        },
        flipCard(index) {
            const card = this.pulledCards[index];
            if (card.flipped || card.revealing) return;

            const rarity = card.rarity;
            let delay = 0;

            if (rarity === 'Common') {
                delay = 0;
            } else if (rarity === 'Rare') {
                card.revealing = true;
                delay = 500;
            } else if (rarity === 'Epic') {
                card.revealing = true;
                delay = 800;
            } else if (rarity === 'Legendary') {
                card.revealing = true;
                delay = 1000;
            } else if (rarity === 'Mythic') {
                card.revealing = true;
                document.body.classList.add('mythic-freeze');
                delay = 1500;
            }

            setTimeout(() => {
                card.revealing = false;
                card.flipped = true;

                if (rarity === 'Mythic') {
                    setTimeout(() => document.body.classList.remove('mythic-freeze'), 1000);
                    this.isShaking = true;
                    setTimeout(() => this.isShaking = false, 500);
                }

                if (navigator.vibrate) {
                    if (rarity === 'Legendary') navigator.vibrate(500);
                    if (rarity === 'Mythic') navigator.vibrate([200, 100, 200, 100, 500]);
                }

                this.allFlipped = this.pulledCards.every(c => c.flipped);
            }, delay);
        },
        //Méthode de fermeture de l'animation
        closeAnim() {
            this.animState = 'hidden';
            this.pulledCards = [];
            this.allFlipped = false;
        },
        getRewardIcon(type) { return '🎁'; },
        getAvatarUrl(avatar) { return avatar && avatar !== 'default_avatar.png' ? `./assets/img/avatars/${avatar}` : `https://ui-avatars.com/api/?name=User&background=00AEEF&color=fff`; }
    }
}