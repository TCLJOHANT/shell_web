import TerminalService from "../Services/TerminalService.js";

const LoginComponent = () => {
  return {
    terminalService: new TerminalService(),
    loginForm: {
      usuario: "",
      password: "",
    },
    async init() {
      Alpine.store("auth").setAuth(await this.terminalService.checkSesion());
    },
    async login() {
      const response = await this.terminalService.login(
        this.loginForm.usuario,
        this.loginForm.password
      );
      // console.log(response);
      if (response && response.status === true) {
        Alpine.store("auth").setAuth(true);
        this.loginForm.usuario = "";
        this.loginForm.password = "";
      } else {
        alert("Usuario o contraseña incorrectos");
      }
    },
  };
};

export default LoginComponent;
