const translations = {
    da: {
        "+ Add feedback": "+ Tilføj feedback",
        "Your feedback": "Din feedback",
        "Tell us what you noticed on this page":
            "Fortæl os hvad du bemærkede på denne side",
        "Your email address": "Din e-mailadresse",
        Description: "Beskrivelse",
        "Describe what happened and what you expected":
            "Beskriv hvad der skete, og hvad du forventede",
        "Submit feedback": "Indsend feedback",
        Cancel: "Annullér",
        "Taking screenshot \u2026": "Tager skærmbillede \u2026",
        "Error taking screenshot": "Fejl ved skærmbillede",
        "Sending feedback \u2026": "Sender feedback \u2026",
        "Feedback created": "Feedback oprettet",
        "Existing feedback": "Eksisterende feedback",
        "Click an element to select": "Klik på et element for at vælge",
        "(optional)": "(valgfrit)",
    },
};

export function t(key, locale) {
    return translations[locale]?.[key] ?? key;
}
