// Fichier de la vue du hub
import HUD from '/anime_game_card/js/components/HUD.js';

export default {
    components: {
        HUD
    },
    template: `
    <div class="hub-view" :class="timeOfDay">
        
        <HUD :user="user" @navigate="navigate" @logout="logout" @open-shop="navigate('ShopView')" />

        <div class="hub-menu">
            <div class="menu-card card-play" @click="navigate('BattleView')">
                <div class="icon">⚔️</div>
                <h2>COMBAT</h2>
            </div>
            <div class="menu-card card-collection" @click="navigate('CollectionView')">
                <div class="icon">🎴</div>
                <h2>GRIMOIRE</h2>
            </div>
            <div class="menu-card card-shop" @click="navigate('ShopView')">
                <div class="icon">✨</div>
                <h2>BOUTIQUE</h2>
            </div>
            <div class="menu-card card-ranking" @click="navigate('LeaderboardView')">
                <div class="icon">🏆</div>
                <h2>CLASSEMENT</h2>
            </div>
            <div class="menu-card card-social" @click="navigate('SocialView')">
                <div class="icon">🤝</div>
                <h2>SOCIAL</h2>
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            user: JSON.parse(localStorage.getItem('user')) || {},
            timeOfDay: 'day-mode'
        }
    },
    mounted() {
        const hour = new Date().getHours();
        this.timeOfDay = (hour >= 20 || hour < 6) ? 'night-mode' : 'day-mode';

        // Rafraîchissement des données au chargement
        this.refreshUserData();
    },
    methods: {
        navigate(viewName) {
            this.$emit('change-view', viewName);
        },
        //Méthode de déconnexion
        logout() {
            if (confirm("Voulez-vous vraiment quitter l'Académie ?")) {
                axios.get('http://localhost/anime_game_card/api/routes.php?action=logout', { withCredentials: true })
                    .then(() => {
                        localStorage.removeItem('user');
                        this.$emit('change-view', 'LoginView');
                    });
            }
        },
        //Méthode de rafraîchissement des données
        async refreshUserData() {
            try {
                const response = await axios.get('http://localhost/anime_game_card/api/routes.php?action=refresh_user', { withCredentials: true });
                if (response.data.user) {
                    this.user = response.data.user;
                    localStorage.setItem('user', JSON.stringify(this.user));
                }
            } catch (error) {
                console.error("Erreur refresh:", error);
                if (error.response && error.response.status === 401) {
                    localStorage.removeItem('user');
                    this.$emit('change-view', 'LoginView');
                }
            }
        }
    }
}