import Alpine from "https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/module.esm.js";
import LoginComponent from "./Components/LoginComponent.js";
import TerminalComponent from "./Components/TerminalComponent.js";
import { ConfiguracionComponent } from "./Components/ConfiguracionComponent.js";

window.Alpine = Alpine;

Alpine.store("auth", {
  estaAutorizado: false,
  setAuth(value) {
    this.estaAutorizado = value;
  }
});

Alpine.store("app", {
  directorioTerminal: "user@web ",
  setDirectorioTerminal(directorioTerminal) {
    this.directorioTerminal = directorioTerminal;
  }
});

Alpine.data("LoginComponent", LoginComponent);
Alpine.data("TerminalComponent", TerminalComponent);
Alpine.data("ConfiguracionComponent", ConfiguracionComponent);
Alpine.start();
