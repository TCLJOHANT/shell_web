import TerminalService from "../Services/TerminalService.js";
import { AnsiUp } from "https://cdn.jsdelivr.net/npm/ansi_up@6.0.6/+esm";

const TerminalComponent = () => {
  return {
    terminalService: new TerminalService(),
    commandInput: "",
    indice_de_lectura: 0,
    comandoEnEjecucion: false,
    terminalOutput: "",
    terminalHtmlOutput: "",
    comandosLocalStorage:
      JSON.parse(localStorage.getItem("comandosLocalStorage")) || [],
    comandoSugerido: "",
    ansi_up: new AnsiUp(),

    async cerrarSesion() {
      let response = await this.terminalService.logout();

      if (response == true) {
        Alpine.store("auth").setAuth(false);
      }
    },
    async ejecutarComando() {
      if (this.comandoEnEjecucion) {
        return;
      }
      this.commandInput = this.commandInput.trim();
      if (this.commandInput.length === 0) {
        alert("No se ha ingresado ningún comando");
        return;
      }

      this.storeCommand(this.commandInput);

      if (this.commandInput === "clear") {
        this.terminalOutput = ""; // Limpiar la salida
        this.terminalHtmlOutput = ""; // Limpiar la salida HTML
        this.indice_de_lectura = 0; // Reiniciar el indice_de_lectura
        this.commandInput = ""; // Limpiar el input
        this.comandoSugerido = ""; // Limpiar el comando sugerido
        return;
      }

      this.comandoEnEjecucion = true;
      let response = await this.terminalService.runComando(this.commandInput);
      if (response.output == "Usted no esta autorizado") {
        alert("No autorizado");
        Alpine.store("auth").setAuth(false);
        this.commandInput = "";
        this.terminalOutput = "";
        this.terminalHtmlOutput = "";
        this.indice_de_lectura = 0; // Reiniciar el indice_de_lectura
        this.comandoEnEjecucion = false;
        return;
      }

      if (response.asynchronous === false) {
        let data = response.output;
        // this.terminalOutput +=
        //   this.$store.app.directorioTerminal + " % " + this.commandInput + "\n";
          this.terminalHtmlOutput +=
          `<span class="prompt">${this.$store.app.directorioTerminal} % ${this.commandInput}</span><br>`;

        //data = data.split("\n");
        // this.terminalOutput += data || "";
        // this.terminalOutput += "\n";
        this.terminalHtmlOutput += this.ansi_up.ansi_to_html(data || "");
        this.terminalHtmlOutput += "<br>";

        this.$nextTick(() => {
          const el = this.$refs.terminalDom;
          el.scrollTop = el.scrollHeight;
          this.commandInput = "";
          this.indice_de_lectura = 0; // Reiniciar el indice_de_lectura
          this.comandoEnEjecucion = false;
          this.comandoSugerido = ""; // Limpiar el comando sugerido
          setTimeout(() => {
            this.$refs.inputComando.focus();
          }, 50);
        });
        return;
      }
      //INICIO LONG POLLING
      // this.terminalOutput +=
      //   this.$store.app.directorioTerminal + " % " + this.commandInput + "\n";

      this.terminalHtmlOutput +=
      `<span class="prompt">${this.$store.app.directorioTerminal} % ${this.commandInput}</span><br>`;

      this.comandoSugerido = "";
      this.commandInput = "";

      const hacerLongPolling = async () => {
        try {
          const data = await this.terminalService.outputComando(
            this.indice_de_lectura
          );
          if (data && data.output) {
            // this.terminalOutput += data.output;
            // Elimina códigos ANSI que mueven cursor o borran línea
            let limpio = data.output.replace(/\x1b\[[0-9;]*[GKF]/g, '');
            this.terminalHtmlOutput += this.ansi_up.ansi_to_html(limpio || "");
            this.$nextTick(() => {
              this.$refs.terminalDom.scrollTop =
                this.$refs.terminalDom.scrollHeight;
            });
            this.indice_de_lectura += data.length;
          }

          if (data && data.done) {
            this.indice_de_lectura = 0;
            this.comandoEnEjecucion = false;
            // this.terminalOutput += "\n";
            this.terminalHtmlOutput += "<br>";
            setTimeout(() => {
              this.$refs.inputComando.focus();
            }, 50);
            return;
          }
          hacerLongPolling();
        } catch (error) {
          console.error("Error en long polling:", error);
          setTimeout(hacerLongPolling, 3000);
        }
      };

      hacerLongPolling();
    },
    sugerenciaComando() {
      const query = this.commandInput.toLowerCase();
      let comandosSugeridos = this.comandosLocalStorage.filter((cmd) =>
        cmd.toLowerCase().startsWith(query)
      );

      if (comandosSugeridos.length > 0 && this.commandInput != "") {
        this.comandoSugerido = comandosSugeridos[0];
      }
    },
    autocompletarComando() {
      if (this.comandoSugerido.length > 0) {
        this.commandInput = this.comandoSugerido; // Autocompletar el comando
      }
    },
    storeCommand(command) {
      if (!this.comandosLocalStorage.includes(command)) {
        this.comandosLocalStorage.push(command);
        localStorage.setItem(
          "comandosLocalStorage",
          JSON.stringify(this.comandosLocalStorage)
        );
      }
    },
  };
};

export default TerminalComponent;
