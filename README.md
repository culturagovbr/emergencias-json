# emergencias-json
Plugin que implementa a geração de um json da programação do Emergencias, para ser consumido por aplicações externas. Ele funciona em cima do tema do Emergencias para WP, que não está disponível no github

Ele também espera que que o QTranslate esteja instalado e rodando, apesar de, teoricamente, funcionar mesmo se não estiver.

Esse plugin gera 3 conjuntos de arquivos jsons: 

1. para os eventos (post type session)

2. para os palestrantes (post type speaker)

3. para os lugares (taxonomia session-location)

Pra cada um desses 3 tipos, são gerados 3 arquivos json, um para cada idioma (os códigos dos idiomas seguem o padrão do QTranslate):

events-en.json
events-pb.json
events-es.json

speakers-en.json
speakers-pb.json
speakers-es.json

spaces-en.json
spaces-pb.json
spaces-es.json

Esses arquivos são gravados dentro da pasta json, colocada dentro da pasta de uploads do WordPress.

Se o plugin não conseguir criar essa pasta por algum problema de permissão, um aviso vai aparecer no admin.

Os jsons de eventos e lugares são gerados sempre que se salva um evento.

O json de speakers é gerado sempre que se salva um speaker.

