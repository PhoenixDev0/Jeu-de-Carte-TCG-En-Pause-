// Fichier de composant pour le HUD
export default {
    props: ['user'],
    template: `
    <nav class="hud-container">
        <div class="hud-left" @click="$emit('navigate', 'ProfileView')" title="Ouvrir le Profil">
            
            <div class="avatar-frame">
                <img v-if="hasCustomAvatar" :src="customAvatarPath" alt="Avatar">
                
                <div v-else class="avatar-placeholder">
                    {{ userInitial }}
                </div>
            </div>
            
            <div class="player-stats">
                <div class="top-row">
                    <span class="player-name">{{ user.username }}</span>
                    <span class="player-title">{{ user.title || 'Novice' }}</span>
                    <span class="player-elo">ELO {{ user.elo }}</span>
                </div>
                
                <div class="xp-bar-container" :title="xpTooltip">
                    <div class="xp-fill" :style="{ width: xpPercentage + '%' }"></div>
                    <div class="level-badge">{{ user.level }}</div>
                </div>
            </div>
        </div>

        <div class="hud-right">
            <div class="currency gold" title="Or">
                <span>💰</span>
                <span>{{ animatedGold }}</span>
            </div>
            
            <div class="currency gems" title="Gemmes">
                <span>💎</span>
                <span>{{ user.gems }}</span>
                <button class="btn-add-gem" @click="$emit('open-shop')" title="Acheter des Gemmes">+</button>
            </div>
            
            <div class="separator"></div>

            <button class="btn-hud" @click="$emit('navigate', 'ProfileView')" title="Paramètres">⚙️</button>
            <button class="btn-hud logout" @click="$emit('logout')" title="Déconnexion">🚪</button>
        </div>
    </nav>
    `,
    computed: {
        hasCustomAvatar() {
            return this.user.avatar && this.user.avatar !== 'default_avatar.png';
        },
        customAvatarPath() {
            return `./assets/img/avatars/${this.user.avatar}`;
        },
        userInitial() {
            return this.user.username ? this.user.username.charAt(0).toUpperCase() : '?';
        },
        maxXp() {
            return this.user.level * 1000;
        },
        xpPercentage() {
            return Math.min((this.user.xp / this.maxXp) * 100, 100);
        },
        xpTooltip() {
            return `Niveau ${this.user.level} - ${this.user.xp} / ${this.maxXp} XP`;
        },
        animatedGold() {
            return this.user.gold;
        }
    }
}