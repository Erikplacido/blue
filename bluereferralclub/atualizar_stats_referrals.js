function atualizarEstatisticasUsuarios() {
  fetch('atualizar_referral_stats.php')
    .then(resp => resp.json())
    .then(data => {
      if (data.success) {
        console.log('✅ Estatísticas de usuários atualizadas com sucesso.');
      } else {
        console.warn('⚠️ Falha ao atualizar estatísticas de usuários.');
        if (data.error) {
          console.error('🔍 Detalhe do erro:', data.error);
        }
      }
    })
    .catch(error => {
      console.error('❌ Erro de rede ou execução ao chamar atualizar_referral_stats.php:', error);
    });
}