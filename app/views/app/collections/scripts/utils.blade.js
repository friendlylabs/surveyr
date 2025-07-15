function extractQuestionsFromSurvey(surveyJson) {
  const questions = [];
  for (const page of surveyJson.pages || []) {
    for (const el of page.elements || []) {
      questions.push(el);
    }
  }
  return questions;
}

function calculateSurveyResults(surveyStructure, responses) {
  const results = {};
  
  surveyStructure = extractQuestionsFromSurvey(surveyStructure);
  for (const question of surveyStructure) {
    const { name, type, title } = question;
    results[name] = {
      question: title,
      type: type,
      totalResponses: 0
    };

    if (type === "radiogroup" || type === "dropdown") {
      const counts = {};
      for (const res of responses) {
        const answer = res[name];
        if (answer) {
          counts[answer] = (counts[answer] || 0) + 1;
          results[name].totalResponses++;
        }
      }
      results[name].answers = counts;

      const percentages = {};
      for (const [key, count] of Object.entries(counts)) {
        percentages[key] = Math.round((count / results[name].totalResponses) * 100);
      }
      results[name].percentages = percentages;

      results[name].mostSelected = Object.entries(counts).sort((a, b) => b[1] - a[1])[0]?.[0] || null;
    }

    else if (type === "checkbox") {
      const counts = {};
      for (const res of responses) {
        const answer = res[name];
        if (Array.isArray(answer)) {
          results[name].totalResponses++;
          for (const item of answer) {
            counts[item] = (counts[item] || 0) + 1;
          }
        }
      }
      results[name].answers = counts;

      const percentages = {};
      for (const [key, count] of Object.entries(counts)) {
        percentages[key] = Math.round((count / results[name].totalResponses) * 100);
      }
      results[name].percentages = percentages;

      results[name].mostSelected = Object.entries(counts).sort((a, b) => b[1] - a[1])[0]?.[0] || null;
    }

    else if (type === "rating" || type === "number") {
      const values = [];
      for (const res of responses) {
        const val = res[name];
        if (typeof val === 'number') {
          values.push(val);
          results[name].totalResponses++;
        }
      }

      const distribution = {};
      for (const val of values) {
        distribution[val] = (distribution[val] || 0) + 1;
      }
      results[name].distribution = distribution;

      const avg = values.reduce((a, b) => a + b, 0) / values.length;
      results[name].average = +avg.toFixed(2);

      values.sort((a, b) => a - b);
      const mid = Math.floor(values.length / 2);
      results[name].median = values.length % 2 !== 0
        ? values[mid]
        : (values[mid - 1] + values[mid]) / 2;
    }

    else if (type === "text" || type === "comment") {
      const texts = [];
      for (const res of responses) {
        const answer = res[name];
        if (typeof answer === "string" && answer.trim() !== "") {
          texts.push(answer.trim());
          results[name].totalResponses++;
        }
      }

      results[name].sampleResponses = texts.slice(0, 3);

      // Basic word frequency (very simple tokenizer)
      const freq = {};
      for (const text of texts) {
        text.toLowerCase().split(/\W+/).forEach(word => {
          if (word.length > 2) { // Ignore short words
            freq[word] = (freq[word] || 0) + 1;
          }
        });
      }

      const sortedFreq = Object.entries(freq)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 10);
      results[name].mostCommonWords = Object.fromEntries(sortedFreq);
    }

    // Add support for more question types if needed...
  }

  return results;
}