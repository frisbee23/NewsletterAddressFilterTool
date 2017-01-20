# NewsletterAddressFilterTool


this is a first version intended only for seasoned sysadmins or programmers.

this script was written to replace a big excel sheet.

newsletter addresses objects have not only an email address but also some more data, like postual address fields, gender, etc. which are stored in the MAIN table ('main').

additonally there are other (lookup-) tables which may represent categories, etc. and corresponding link-tables.
for example we have a record-company which links the persons=newsletter address objects in main table, to genres the persons like. this links are saved in nm_genres_main.

there are no routines for consistency, so the lookup tables should be considerd write-once-never-delete.

good luck :D
