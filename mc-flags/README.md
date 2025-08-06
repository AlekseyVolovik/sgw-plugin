1. Дублируем workflow из темы
2. Меняем ветку с master на main
3. По желанию меняем node-version: с [14.x] на [18.x]
4. Меняем версии actions на 3
   - \- uses: actions/checkout@v3
   - \- uses: actions/setup-node@v3
5. Меняем sercrets key
6. Заменяем команды из Build на
      - \- run: rm -rf .git .github
      - \- run: rm .gitignore
7. Меняем путь с темы на деплой в нужную дерикторию (для матч-центра это: /plugins/MatchCenter/countries_flags/)
