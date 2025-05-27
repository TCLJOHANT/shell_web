export const ConfiguracionComponent = () => {
  return {
    colorTheme:
      localStorage.getItem("colorTheme") ||
      getComputedStyle(document.documentElement)
        .getPropertyValue("--color-primario")
        .trim(),

    init() {
      document.documentElement.style.setProperty(
        "--color-primario",
        this.colorTheme
      );
    },

    configuracion() {
      Swal.fire({
        title: "Configuración",
        html: `
          <div class="form-group text-start">
            <label for="colorPicker" class="form-label">Selecciona el color:</label>
            <input type="color" id="colorPicker" class="form-control form-control-color mt-2" value="${this.colorTheme}" title="Elige tu color">
          </div>
        `,
        showCloseButton: true,
        confirmButtonText: '<i class="bi bi-check-circle"></i> Aplicar',
        customClass: {
          popup: "p-4 rounded",
          confirmButton: "btn btn-primary mt-3",
        },
        preConfirm: () => {
          return document.getElementById("colorPicker").value;
        },
      }).then((result) => {
        if (result.isConfirmed && result.value) {

          localStorage.setItem("colorTheme", result.value);
          document.documentElement.style.setProperty(
            "--color-primario",
            result.value
          );
        }
      });
    },
  };
};
