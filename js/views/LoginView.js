// Fichier de la vue de connexion

export default {
    template: `
    <div class="login-view">
        <div class="anime-bg"></div>

        <div v-if="state === 'title'" class="title-screen" @click="goToLogin">
            <h1 class="game-logo glitch" data-text="ANIME GAME CARD">ANIME GAME CARD</h1>
            <p class="subtitle">V2.0 - CHRONICLES</p>
            <div class="press-start blink">
                >>> PRESS START <<<
            </div>
        </div>

        <div v-else class="auth-container glass-panel">
            
            <div class="auth-tabs">
                <button :class="{active: isLogin}" @click="isLogin = true">Connexion</button>
                <button :class="{active: !isLogin}" @click="isLogin = false">Inscription</button>
            </div>

            <form v-if="isLogin" @submit.prevent="handleLogin">
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" v-model="loginForm.email" required placeholder="hero@anime.com">
                </div>
                <div class="input-group">
                    <label>Mot de passe</label>
                    <input type="password" v-model="loginForm.password" required>
                </div>
                <button type="submit" class="btn-action">SE CONNECTER</button>
            </form>

            <form v-else @submit.prevent="handleRegister">
                <div class="input-group">
                    <label>Pseudo (Nom de Héros)</label>
                    <input type="text" v-model="registerForm.username" required placeholder="GokuDu54">
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" v-model="registerForm.email" required>
                </div>
                <div class="input-group">
                    <label>Mot de passe</label>
                    <input type="password" v-model="registerForm.password" required>
                </div>
                <button type="submit" class="btn-action">REJOINDRE L'ACADÉMIE</button>
            </form>

            <p v-if="message" class="message-box" :class="messageType">{{ message }}</p>
            
            <button class="btn-back" @click="state = 'title'">Retour</button>
        </div>
    </div>
    `,
    data() {
        return {
            state: 'title',
            isLogin: true,
            message: '',
            messageType: '',
            loginForm: {
                email: '',
                password: ''
            },
            registerForm: {
                username: '',
                email: '',
                password: ''
            }
        }
    },
    methods: {
        goToLogin() {
            this.state = 'auth';
        },
        //Méthode de connexion
        async handleLogin() {
            try {
                this.message = "Connexion en cours...";
                const response = await axios.post(
                    'http://localhost/anime_game_card/api/routes.php?action=login',
                    this.loginForm,
                    { withCredentials: true }
                );

                if (response.data.user) {
                    this.message = "Connexion réussie !";
                    this.messageType = "success";
                    localStorage.setItem('user', JSON.stringify(response.data.user));
                    setTimeout(() => {
                        this.$emit('change-view', 'HubView');
                    }, 1000);
                } else {
                    // Cas où la réponse est reçue mais sans user (échec de connexion)
                    this.message = response.data.message || "Erreur de connexion.";
                    this.messageType = "error";
                }
            } catch (error) {
                console.error("Login Error Details:", error);
                // Afficher le vrai message d'erreur du backend
                if (error.response && error.response.data && error.response.data.message) {
                    this.message = error.response.data.message;
                } else if (error.request) {
                    this.message = "Pas de réponse du serveur. Vérifiez que XAMPP est lancé.";
                } else {
                    this.message = "Erreur de connexion: " + error.message;
                }
                this.messageType = "error";
            }
        },
        //Méthode d'inscription
        async handleRegister() {
            try {
                this.message = "Inscription en cours...";
                const response = await axios.post('http://localhost/anime_game_card/api/routes.php?action=register', this.registerForm);

                if (response.status === 201) {
                    this.message = "Compte créé ! Veuillez vous connecter.";
                    this.messageType = "success";
                    this.isLogin = true;
                    this.registerForm = { username: '', email: '', password: '' };
                }
            } catch (error) {
                console.error("Erreur complète:", error);
                let errorText = "Erreur inconnue";

                if (error.response && error.response.data) {
                    if (error.response.data.message) {
                        errorText = error.response.data.message;
                    } else if (error.response.data.error) {
                        errorText = "DB Error: " + error.response.data.error;
                    } else {
                        errorText = "CRASH PHP: " + JSON.stringify(error.response.data).substring(0, 100);
                    }
                } else {
                    errorText = "Erreur réseau : Vérifiez que l'adresse http://localhost/anime_game_card/ existe bien.";
                }

                this.message = errorText;
                this.messageType = "error";
            }
        }
    }
}