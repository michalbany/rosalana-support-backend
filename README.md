# Rosalana Support (Backend)

![Static Badge](https://img.shields.io/badge/ROSALANA-blue?style=for-the-badge)


**Rosalana Support** představuje místo pro získání podpory a pomoci s produkty v ekosystému Rosalana. Uživatel si může vybrat s kterým produktem má problém a následně se mu zobrazí nápověda a možnost kontaktovat podporu. 

Posílání zpráv a dalších oznámení je zajištěno pomocí **Rosalana Notifications** modulu, jakožto jednotného modulu pro oznámení v ekosystému.

> Představuje ukázku implementace backendu pro Rosalana Accounts. 

## Funkce

Kromě zákaznické podpory slouží jako jednotné Admin Centrum napříč všemi produkty Rosalana. Fetchuje veřejné API z aplikací a je možné s nimi manipulovat. Tato funkce je přístupná pouze pro Admin uživatele. Admin uživatelé jsou uloženi v lokální databázi na Rosalana Support serveru. Tato databáze je synchronizována s Rosalana Accounts. Jedná se pouze o přiřazení role, což ukazuje krásnou implementaci mezi surovými daty v Accounts a vlastní logikou v konkrétní aplikaci.

## Implementace Rosalana Accounts
Authetifikace funguje na bázi JWT tokenu. Který se potom převádí na RA-TOKEN cookie. Tato cookie se posílá na frontend. Pokud je cookie aktivní, znamená to že je uživatel na serveru přihlášen. 

Cookie je potřeba k posílání requestů na Rosalana Accounts.

Response z API používá standart message, data, errors.

```json
{
    "message": "Success",
    "data": {}
}
```

Když dojde k erroru tak response obsahuje buď `error` nebo `errors` pole. Pokud dojde k chybě je tato celá chyba posílaná na frontend jako `Exception`.


## Todo
- [x] Login/Logout/Registration (Rosalana Accounts)
- [x] Převést na JSON API Standard
- [x] Správné zpracování Rosalana Accounts Response (frontend->server->RA->server->frontend) Odesílat stejný response jako Rosalana Accounts. Pouze odstranit informace, které nemají být zobrazeny. Nyní když uživatel registruje uživatele, který již existuje dostane zpět chybu ale s nevypovídajícími informacemi.
- [x] Auth::class lépe organizovaná pro znovu použitelnost (login, logout -> při chybě nebo naopak úspěchu např. při refreshi tokenu)
- [x] Global API Response Trait
- [ ] Global Resource API Responses (např. UserResource JSON_API_STANDARD)
- [ ] Admin Centrum
- [ ] Veřejné API endpointy (nastavení CORS - veřejně povolit pouze /api/*)
- [ ] Podpora pro Rosalana Notifications

### Note

Blueprint: a platform focused on facilitating communication between agencies and clients

Rosalana: a social network focused on creativity and creative expression

ProxyMa: a CMS system designed based on a single central administration

S Transfer: a platform that lets you transfer files between devices
