// Fichier de la vue du profil
import HUD from '/anime_game_card/js/components/HUD.js';

export default {
    components: { HUD },
    template: `
    <div class="profile-view">
        <HUD :user="user" @navigate="$emit('change-view', $event)" @logout="logout" />

        <button class="btn-back-hub" @click="$emit('change-view', 'HubView')">⬅ Retour au QG</button>

        <div class="profile-container glass-panel">
            <div class="profile-tabs">
                <button @click="activeTab = 'identity'" :class="{active: activeTab === 'identity'}">🆔 Identité</button>
                <button @click="activeTab = 'stats'" :class="{active: activeTab === 'stats'}">📊 Données Combat</button>
                <button @click="activeTab = 'system'" :class="{active: activeTab === 'system'}">⚙️ Système</button>
            </div>

            <div class="profile-content">
                
                <div v-if="activeTab === 'identity'" class="tab-content identity-tab">
                    <div class="profile-card">
                        <div class="profile-left">
                            <div class="avatar-editor" @click="showAvatarModal = true">
                                <img :src="userAvatar" alt="Avatar">
                                <div class="edit-overlay">✏️ MODIFIER</div>
                            </div>
                            <div class="rank-badge">{{ getRankName(user.elo) }}</div>
                        </div>
                        
                        <div class="profile-right">
                            <h2 class="username">{{ user.username }} <span class="tag">#{{ user.friend_code.split('#')[1] }}</span></h2>
                            
                            <div class="form-group">
                                <label>Titre actuel</label>
                                <select v-model="user.title" @change="saveProfile" class="input-glass">
                                    <option v-for="t in availableTitles" :value="t">{{ t }}</option>
                                </select>
                            </div>

                            <div class="xp-section">
                                <div class="xp-labels">
                                    <span>Niveau {{ user.level }}</span>
                                    <span>{{ user.xp }} / {{ user.level * 1000 }} XP</span>
                                </div>
                                <div class="xp-progress">
                                    <div class="xp-bar" :style="{width: xpPercentage + '%'}"></div>
                                </div>
                            </div>
                            
                            <div class="stats-mini">
                                <div>📅 Inscrit le : {{ new Date(user.created_at).toLocaleDateString() }}</div>
                                <div>🏆 ELO : <span class="gold-text">{{ user.elo }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'stats'" class="tab-content stats-tab">
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-val">{{ user.wins + user.losses }}</div>
                            <div class="stat-label">Parties</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val win">{{ user.wins }}</div>
                            <div class="stat-label">Victoires</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val lose">{{ user.losses }}</div>
                            <div class="stat-label">Défaites</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val gold-text">{{ winRate }}%</div>
                            <div class="stat-label">Ratio</div>
                        </div>
                    </div>

                    <h3>Historique Récent</h3>
                    <div class="history-list">
                        <p v-if="matchHistory.length === 0" class="empty-msg">Aucun match enregistré.</p>
                        <div v-for="match in matchHistory" :key="match.date" class="history-item" :class="match.result === 'VICTOIRE' ? 'win' : (match.result === 'DÉFAITE' ? 'lose' : 'draw')">
                            <div class="match-result">{{ match.result }}</div>
                            <div class="match-vs">vs {{ match.opponent }}</div>
                            <div class="match-time">{{ match.duration }}</div>
                            <div class="match-date">{{ new Date(match.date).toLocaleDateString() }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'system'" class="tab-content system-tab">
                    <div class="settings-group">
                        <h3>Audio</h3>
                        <div class="slider-group">
                            <label>Musique (BGM)</label>
                            <input type="range" v-model="settings.bgmVolume" min="0" max="100" @input="saveSettings">
                        </div>
                        <div class="slider-group">
                            <label>Effets (SFX)</label>
                            <input type="range" v-model="settings.sfxVolume" min="0" max="100" @input="saveSettings">
                        </div>
                    </div>

                    <div class="settings-group">
                        <h3>Sécurité</h3>
                        <form @submit.prevent="changePassword" class="password-form">
                            <input type="password" v-model="pwd.old" placeholder="Ancien mot de passe" required class="input-glass">
                            <input type="password" v-model="pwd.new" placeholder="Nouveau mot de passe" required class="input-glass">
                            <button type="submit" class="btn-action">Changer le mot de passe</button>
                        </form>
                        <p v-if="pwdMsg" :class="pwdSuccess ? 'msg-success' : 'msg-error'">{{ pwdMsg }}</p>
                    </div>
                    
                    <button class="btn-danger" @click="logout">Se Déconnecter</button>
                </div>
            </div>
        </div>

        <div v-if="showAvatarModal" class="modal-overlay" @click.self="showAvatarModal = false">
            <div class="modal-window">
                <h3>Choisir un Avatar</h3>
                <div class="avatar-grid">
                    <div v-for="av in availableAvatars" :key="av" class="avatar-option" @click="selectAvatar(av)">
                         <img :src="getAvatarUrl(av)" alt="Avatar">
                    </div>
                </div>
                <button class="btn-close" @click="showAvatarModal = false">Fermer</button>
            </div>
        </div>

    </div>
    `,
    data() {
        return {
            user: JSON.parse(localStorage.getItem('user')) || {},
            activeTab: 'identity',
            showAvatarModal: false,
            matchHistory: [],
            availableAvatars: [],
            availableTitles: [],
            settings: {
                bgmVolume: localStorage.getItem('bgmVolume') || 50,
                sfxVolume: localStorage.getItem('sfxVolume') || 50
            },
            pwd: { old: '', new: '' },
            pwdMsg: '',
            pwdSuccess: false
        }
    },
    computed: {
        userAvatar() {
            return this.getAvatarUrl(this.user.avatar);
        },
        xpPercentage() {
            const maxXp = this.user.level * 1000;
            return Math.min((this.user.xp / maxXp) * 100, 100);
        },
        winRate() {
            const total = this.user.wins + this.user.losses;
            if (total === 0) return 0;
            return Math.round((this.user.wins / total) * 100);
        }
    },
    mounted() {
        this.loadProfileData();
    },
    methods: {
        //Méthode de chargement des données du profil
        async loadProfileData() {
            try {
                const res = await axios.get('http://localhost/anime_game_card/api/routes.php?action=get_profile_data', { withCredentials: true });
                this.user = res.data.user;
                this.matchHistory = res.data.history;
                this.availableAvatars = res.data.avatars;
                this.availableTitles = res.data.titles;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (e) { console.error("Erreur profil", e); }
        },
        //Méthode de récupération du nom du rang
        getRankName(elo) {
            if (elo < 1200) return "Eveil";
            if (elo < 1500) return "Initié";
            if (elo < 1800) return "Gardien";
            if (elo < 2100) return "Virtuose";
            return "Omniscient";
        },
        //Méthode de récupération de l'URL de l'avatar
        getAvatarUrl(avatarName) {
            if (avatarName && avatarName !== 'default_avatar.png') {
                return `./assets/img/avatars/${avatarName}`;
            }
            return `https://ui-avatars.com/api/?name=${this.user.username}&background=00AEEF&color=fff&size=128&bold=true`;
        },
        //Méthode de sauvegarde du profil
        async saveProfile() {
            await axios.post('http://localhost/anime_game_card/api/routes.php?action=update_profile', {
                avatar: this.user.avatar,
                title: this.user.title
            }, { withCredentials: true });
        },
        //Méthode de sélection de l'avatar
        async selectAvatar(av) {
            this.user.avatar = av;
            await this.saveProfile();
            this.showAvatarModal = false;
        },
        saveSettings() {
            localStorage.setItem('bgmVolume', this.settings.bgmVolume);
            localStorage.setItem('sfxVolume', this.settings.sfxVolume);
        },
        async changePassword() {
            this.pwdMsg = "Traitement...";
            try {
                await axios.post('http://localhost/anime_game_card/api/routes.php?action=change_password', {
                    old_password: this.pwd.old,
                    new_password: this.pwd.new
                }, { withCredentials: true });
                this.pwdMsg = "Mot de passe modifié avec succès !";
                this.pwdSuccess = true;
                this.pwd = { old: '', new: '' };
            } catch (e) {
                this.pwdMsg = e.response?.data?.message || "Erreur";
                this.pwdSuccess = false;
            }
        },
        logout() {
            if (confirm("Se déconnecter ?")) {
                axios.get('http://localhost/anime_game_card/api/routes.php?action=logout', { withCredentials: true })
                    .then(() => {
                        localStorage.removeItem('user');
                        this.$emit('change-view', 'LoginView');
                    });
            }
        }
    }
}