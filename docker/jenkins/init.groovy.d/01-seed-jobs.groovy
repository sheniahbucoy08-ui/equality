import jenkins.model.*
import hudson.model.*

def jenkins = Jenkins.getInstanceOrNull()
if (jenkins == null) {
    println "Jenkins not ready — skip job seed"
    return
}

def seedDir = new File("/usr/share/jenkins/ref/jobs-seed")
if (!seedDir.exists()) {
    println "No jobs-seed directory"
    return
}

seedDir.eachDir { File jobDir ->
    def config = new File(jobDir, "config.xml")
    if (!config.exists()) return

    def name = jobDir.name
    def target = new File(jenkins.getRootDir(), "jobs/${name}")
    def targetConfig = new File(target, "config.xml")

    if (!targetConfig.exists() || config.text != targetConfig.text) {
        target.mkdirs()
        targetConfig.text = config.text
        println "Seeded job: ${name}"
    }
}

jenkins.reload()
