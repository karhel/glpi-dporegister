# GLPI DPO-REGISTER
Processings Register for DPO (GDPR) - GLPI Plugin

This plugin makes GLPI to create a global Processings Register for any of your entities.
You will be able to declare the DPO and the Legal representative (based on the users database) of each one and define a "corporate name", which will be used in the PDF export.

* Processings inventory
* Personal Data Categories
* Categories of Individuals
* Security Mesures
* Export in PDF
* Rights management

## Translation
You can find or propose translations in Transifex at https://www.transifex.com/--161/glpi-plugin-dpo-register

## Documentation
### Installation

### Right management
By defaut, noone can access to the Register (item in Management).
Apply correct rights management in each profiles concerned by the RGPD

### Populate the dropdowns
3 dropdowns were created : Personal Data Categories, Categories of Individuals and Security Mesures;
You can populate each of these in the menu Setup > Dropdowns.
Verify rights if they don't appear in the list.

### Create Processing
You can create the processings list in the menu Management > Processings.
You can also create a PDF using de corresponding tab.
Verify entities informations in case of errors.

### Entities Informations
A special tab was added to the Entity item page : GDPR.
You have to indicate the Legal representative and the DPO and the corporate name which will be used in the PDF exports (on the Processing item and Entity item).

## Contributing

* Open a ticket for each bug/feature so it can be discussed
* Follow [development guidelines](http://glpi-developer-documentation.readthedocs.io/en/latest/plugins/index.html)
* Refer to [GitFlow](http://git-flow.readthedocs.io/) process for branching
* Work on a new branch on your own fork
* Open a PR that will be reviewed by a developer
