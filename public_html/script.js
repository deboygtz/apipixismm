document.addEventListener("DOMContentLoaded", () => {
  // Função para mostrar/ocultar inputs e labels
  function toggleInput(idLabel, idInput, show) {
    const label = document.getElementById(idLabel);
    const input = document.getElementById(idInput);
    if (label) label.style.display = show ? "block" : "none";
    if (input) input.style.display = show ? "block" : "none";
  }

  // Controle de visibilidade do embed de entrega
  const embedEntrega = document.getElementById("embed-entrega");
  if (embedEntrega) {
    embedEntrega.style.display = config.showEntrega ? "block" : "none";
  }

  toggleInput("label-nome", "nome", config.showNome);
  toggleInput("label-email", "email", config.showEmail);
  toggleInput("label-cpfcnpj", "cpfcnpj", config.showCpfCnpj);

  const labelCel = document.getElementById("label-celular");
  const groupCel = document.getElementById("group-celular");
  if (labelCel) labelCel.style.display = config.showCelular ? "block" : "none";
  if (groupCel) groupCel.style.display = config.showCelular ? "flex" : "none";

  // Função para formatar valor em real
  function formatValor(valor) {
    // Recebe número ou string, retorna string formatada em R$ 99,99
    let num = typeof valor === "string" ? parseFloat(valor.replace(",", ".")) : valor;
    if (isNaN(num)) return "R$ 0,00";
    return "R$ " + num.toFixed(2).replace(".", ",");
  }

  // Atualiza valores na tela
  const valorFormatado = formatValor(config.valorProduto);

  const valorTotal = document.getElementById("valor-total");
  if (valorTotal) valorTotal.innerHTML = `Valor à vista: <strong>${valorFormatado}</strong>`;

  const resumoValor = document.getElementById("resumo-valor");
  if (resumoValor) resumoValor.textContent = valorFormatado;

  const nomeProduto = document.getElementById("nome-produto");
  if (nomeProduto) nomeProduto.textContent = config.nomeProduto;

  const valorProduto = document.getElementById("valor-produto");
  if (valorProduto) valorProduto.textContent = valorFormatado;

  const imagemProduto = document.getElementById("imagem-produto");
  if (imagemProduto) imagemProduto.src = config.imagemProduto;

  // Botão Comprar: texto, cor, hover
  const botaoComprar = document.getElementById("botao-comprar");
  if (botaoComprar) {
    botaoComprar.textContent = config.textoBotaoComprar;
    botaoComprar.style.backgroundColor = config.corBotaoComprar;

    botaoComprar.addEventListener("mouseenter", () => {
      botaoComprar.style.backgroundColor = shadeColor(config.corBotaoComprar, -15);
    });
    botaoComprar.addEventListener("mouseleave", () => {
      botaoComprar.style.backgroundColor = config.corBotaoComprar;
    });
  }

  // Função para escurecer ou clarear cor hexadecimal
  function shadeColor(color, percent) {
    let f = parseInt(color.slice(1), 16),
      t = percent < 0 ? 0 : 255,
      p = Math.abs(percent) / 100,
      R = f >> 16,
      G = (f >> 8) & 0x00ff,
      B = f & 0x0000ff;
    return (
      "#" +
      (
        0x1000000 +
        (Math.round((t - R) * p) + R) * 0x10000 +
        (Math.round((t - G) * p) + G) * 0x100 +
        (Math.round((t - B) * p) + B)
      )
        .toString(16)
        .slice(1)
    );
  }

  // Banner - inicializa src do banner
  const bannerImg = document.getElementById("banner-img");
  if (bannerImg && config.bannerUrl) {
    bannerImg.src = config.bannerUrl;
  }

  // Caso exista input para alterar URL do banner em tempo real
  const bannerUrlInput = document.getElementById("banner-url");
  if (bannerUrlInput) {
    bannerUrlInput.value = config.bannerUrl;

    bannerUrlInput.addEventListener("input", () => {
      if (bannerImg) bannerImg.src = bannerUrlInput.value;
    });
  }

  // Máscara e consulta CEP
  const cepInput = document.getElementById("cep");
  if (cepInput) {
    // Máscara para CEP (xxxxx-xxx)
    cepInput.addEventListener("input", function () {
      let v = this.value.replace(/\D/g, "");
      if (v.length > 5) v = v.slice(0, 5) + "-" + v.slice(5, 8);
      this.value = v;
    });

    // Consulta CEP ao sair do campo
    cepInput.addEventListener("blur", function () {
      const cep = this.value.replace(/\D/g, "");
      if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
          .then((response) => response.json())
          .then((data) => {
            if (!data.erro) {
              document.getElementById("logradouro").value = data.logradouro || "";
              document.getElementById("bairro").value = data.bairro || "";
              document.getElementById("cidade").value = data.localidade || "";
              document.getElementById("uf").value = data.uf || "";
            } else {
              alert("CEP não encontrado.");
              document.getElementById("logradouro").value = "";
              document.getElementById("bairro").value = "";
              document.getElementById("cidade").value = "";
              document.getElementById("uf").value = "";
            }
          })
          .catch(() => {
            alert("Erro ao consultar CEP.");
          });
      }
    });
  }
});
