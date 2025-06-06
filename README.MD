<p align="center">
  <img src="https://raw.githubusercontent.com/friendlylabs/surveyr/refs/heads/main/public/assets/images/brand/logo.png" alt="Surveyr Logo" width="200" />
</p>

# README

Surveyr is an open-source form builder that allows anyone to create engaging forms for surveys, questionnaires, quizzes, and polls.

The project is still in the early stages of development, while I am doing my best to make it as stable as possible, there are still a numberof features that need to be implemented and improvements to be made, for more information on the current state of the project, please refer to the [project board](https://github.com/orgs/friendlylabs/projects/3?pane=issue&itemId=112757991).

- demo: [https://surveyr.saasify.io](https://surveyr.saasify.io)
- user: admin@admin.com
- pass: password

<p align="center">
  <img src="https://i.ibb.co/5X6vVc7P/ezgif-6ebc5c4b2085b3.gif" alt="Surveyr Demo" />
</p>

## Features

- 📝 **Drag and Drop Builder:** Effortlessly design forms using an intuitive drag-and-drop interface. Add various question types, text fields, dropdowns, checkboxes, and more with just a few clicks. The builder simplifies creating interactive and visually appealing forms, making it accessible for non-technical users.
- 🧠 **Conditional Logic and Branching:** Implement powerful conditional logic to show or hide questions based on user responses. This feature allows you to create personalized, dynamic forms that adapt to each user's answers, enhancing engagement and relevance.
- 🔗 **Webhooks Integration:** Seamlessly connect your forms with other platforms and automate workflows using webhooks. Push form submissions to your CRM, send notifications to Slack, trigger emails, or integrate with third-party services to streamline your data processing.
- 🖼️ **Tailored Templates:** Get started quickly with a variety of pre-designed templates tailored for different use cases like customer feedback, event registrations, quizzes, and more. Customize these templates to fit your brand's look and feel.
- 📊 **Insightful Analytics:** Gain valuable insights from your data with built-in analytics. Track form performance, response rates, and user behavior. Visualize your data with charts and graphs to make informed decisions and optimize your surveys for better results.
- 📤 **Flexible Data Export:** Easily export your form data in multiple formats, such as CSV, Excel, or JSON. This flexibility ensures that you can analyze your data offline or import it into other tools for deeper analysis.
- 📶 **[Offline Data Collection](https://github.com/friendlylabs/surveyr-app):** Collect responses even when there's no internet connection. Once the device is back online, the data automatically syncs to ensure you never miss any submissions. This feature is perfect for field surveys, remote areas, or on-the-go data collection, Get the client from the [Play Store](https://play.google.com/store/apps/details?id=co.surveyr.collect)
- 🤖 **AI Generated Forms:** Let AI generate forms based on your requirements. This feature can help you create complex forms quickly and efficiently, saving time and effort. The AI engine analyzes your input and generates a form structure that you can further customize to meet your needs.

## Tech Stack & Requirements

Surveyr is built using modern technologies and follows best practices to deliver a robust and scalable form builder solution. Here's an overview of the tech stack used in the project:

- PHP 8+
- MySQL 5.7+, MySQL 8.0+

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

Surveyr is open-source software that leverages proprietary SurveyJS components for its form-building and survey data analysis capabilities. Regardless of the use case, you are required to obtain a developer license from SurveyJS to use the Surveyr platform in your application. For more information on licensing, please refer to the[SurveyJS licensing page](https://surveyjs.io/licensing) and the [EULA](https://surveyjs.io/Developer-License-Agreement.pdf).

## Credits

Surveyr is developed and maintained by [Friendly Labs](https://friendlylabs.io/), a team passionate about building tools that empower individuals and organizations to create, collaborate, and innovate. We believe in the power of open-source software to drive positive change and foster a more inclusive and accessible digital world.

- [surveyjs](https://surveyjs.io) - For the survey builder library
- [leafphp](https://leafphp.dev) - Project Framework
- [bootstrap](https://getbootstrap.com) - Frontend Framework
- [fontawesome](https://fontawesome.com) - Icons

**Disclaimer:**

1. This project is not affiliated with or endorsed by SurveyJS, LeafPHP, Bootstrap, or FontAwesome. The names and logos are trademarks of their respective owners.
2. Surveyr leverages **SurveyJS** for its form-building capabilities, Regardless of the use case, you are required to obtain a developer license from SurveyJS to use the Surveyr platform in your application. For more information on licensing, please refer to the [SurveyJS licensing page](https://surveyjs.io/licensing) and the [EULA](https://surveyjs.io/Developer-License-Agreement.pdf).
