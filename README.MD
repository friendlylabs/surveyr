<p align="center">
  <img src="https://raw.githubusercontent.com/friendlylabs/surveyr/refs/heads/main/public/assets/images/brand/logo.png" alt="Surveyr Logo" width="200" />
</p>

# README

Surveyr is an open-source form builder that allows anyone to create engaging forms for surveys, questionnaires, quizzes, and polls.

The project is still in the early stages of development, while I am doing my best to make it as stable as possible, there are still a numberof features that need to be implemented and improvements to be made, for more information on the current state of the project, please refer to the [project board](https://github.com/orgs/friendlylabs/projects/3/views/1).

<p align="center">
  <img src="https://surveyr.saasify.io/assets/pwa/screenshots/1.jpg" alt="Surveyr Screenshot" />
</p>

## Features

- 📝 **Drag and Drop Builder:** Effortlessly design forms using an intuitive drag-and-drop interface. Add various question types, text fields, dropdowns, checkboxes, and more with just a few clicks. The builder simplifies creating interactive and visually appealing forms, making it accessible for non-technical users.
- 🧠 **Conditional Logic and Branching:** Implement powerful conditional logic to show or hide questions based on user responses. This feature allows you to create personalized, dynamic forms that adapt to each user's answers, enhancing engagement and relevance.
- 🔗 **Webhooks Integration:** Seamlessly connect your forms with other platforms and automate workflows using webhooks. Push form submissions to your CRM, send notifications to Slack, trigger emails, or integrate with third-party services to streamline your data processing.
- 🖼️ **Tailored Templates:** Get started quickly with a variety of pre-designed templates tailored for different use cases like customer feedback, event registrations, quizzes, and more. Customize these templates to fit your brand's look and feel.
- 📊 **Insightful Analytics:** Gain valuable insights from your data with built-in analytics. Track form performance, response rates, and user behavior. Visualize your data with charts and graphs to make informed decisions and optimize your surveys for better results.
- 📤 **Flexible Data Export:** Easily export your form data in multiple formats, such as CSV, Excel, or JSON. This flexibility ensures that you can analyze your data offline or import it into other tools for deeper analysis.
- 📶 **Offline Data Collection (Coming Soon):** Collect responses even when there's no internet connection. Once the device is back online, the data automatically syncs to ensure you never miss any submissions. This feature is perfect for field surveys, remote areas, or on-the-go data collection.

## System dependencies

- PHP 8+
- MySQL 5.7+

## Getting started

- Fork the repository or install from composer

  ```
  composer create-project friendlylabs/surveyr
  ```
- Run the migrations and seed the database

  ```
  php leaf db:migrate
  ```

  ```
  php leaf db:seed
  ```
- Start the development server

  ```
  php leaf serve
  ```

  The API documentation can be found [here](https://documenter.getpostman.com/view/14546504/2sAYQiBo9h)

## Contributing

We welcome contributions from the community. If you have ideas for new features, bug fixes, or enhancements, feel free to submit a pull request. For major changes, please open an issue first to discuss the proposed changes.

## License

Surveyr is open-source software licensed under the [GNU Affero General Public License v3.0 (AGPL-3.0)](https://www.gnu.org/licenses/agpl-3.0.html). You are free to use, modify, and distribute the software under the terms of this license.

## Credits

Surveyr is developed and maintained by [Friendly Labs](https://friendlylabs.io/), a team passionate about building tools that empower individuals and organizations to create, collaborate, and innovate. We believe in the power of open-source software to drive positive change and foster a more inclusive and accessible digital world.

- [surveyjs](https://surveyjs.io) - For the survey builder library
- [leafphp](https://leafphp.dev) - Project Framework
- [bootstrap](https://getbootstrap.com) - Frontend Framework
- [fontawesome](https://fontawesome.com) - Icons

**Disclaimer:**

1. This project is not affiliated with or endorsed by the surveyjs, leafphp, bootstrap, or fontawesome projects. The names and logos are trademarks of their respective owners.
2. Surveyr leverages **SurveyJS** for its form-building capabilities. With that being said Surveyr must strictly be used for non-commercial purposes unless you obtain a developer license from SurveyJS. For more information, please review the [SurveyJS License.](https://surveyjs.io/licensing)  Also [read](https://surveyjs.answerdesk.io/ticket/details/t20848/is-developer-license-required-for-opensource-tools) this QA thread
