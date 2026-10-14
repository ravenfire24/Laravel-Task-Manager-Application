# Public certificate authorities

`cacert.pem` is the Mozilla CA bundle distributed by https://curl.se/ca/cacert.pem,
retrieved over verified HTTPS on 2026-10-13. It contains public CA certificates,
not application credentials or private keys. Its licensing notice is in the file.

Refresh this bundle periodically from the same HTTPS source. PostgreSQL on Vercel
uses it with `sslmode=verify-full` to check both certificate trust and hostname.
For a private CA, set `DB_SSLROOTCERT` to your trusted public CA file. Local
PostgreSQL keeps its configured TLS mode unless `DB_VERIFY_SERVER_CERTIFICATE=true`.
