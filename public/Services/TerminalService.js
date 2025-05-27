class TerminalService {
  URL = window.location.origin + "/terminal/public/index.php";
  constructor() {}

  //AUTH
  async login(usuario, password) {
    try {
      const response = await fetch(this.URL, {
        method: "POST",
        body: JSON.stringify({ usuario, password }),
      });
      if (response.ok) {
        let data = await response.json();
        if (data?.data?.directorioTerminal) {
          Alpine.store("app").setDirectorioTerminal(
            data.data.directorioTerminal
          );
        }
        //console.log(data)
        return data;
      }
    } catch (e) {
      console.error(e);
      return false;
    }
  }

  async checkSesion() {
    try {
      const res = await fetch(this.URL + "?checkSesion");
      const data = await res.json();
      if (data?.data?.directorioTerminal) {
        Alpine.store("app").setDirectorioTerminal(data.data.directorioTerminal);
      }
      if (data.status) {
        return true;
      }
      return false;
    } catch (e) {
      console.error(e);
      return false;
    }
  }

  async logout() {
    try {
      let response = await fetch(`${this.URL}?logout`);
      let data = await response.json();

      return data.status;
    } catch (e) {
      console.error(e);
      return false;
    }
  }

  //COMANDOS DE TERMINAL
  async runComando(command) {
    try {
      const response = await fetch(this.URL, {
        method: "POST",
        body: JSON.stringify({ command }),
      });

      let data = await response.json();
      console.log(data);
      if (data?.data?.directorioTerminal) {
        Alpine.store("app").setDirectorioTerminal(data.data.directorioTerminal);
      }
      return data;
    } catch (e) {
      console.error(e);
      return false;
    }
  }

  async outputComando(indiceDeLectura) {
    try {
      const response = await fetch(
        this.URL + "?indiceDeLectura=" + indiceDeLectura
      );
      let data = await response.json();
      return data;
    } catch (e) {
      console.error(e);
      return false;
    }
  }
}

export default TerminalService;
